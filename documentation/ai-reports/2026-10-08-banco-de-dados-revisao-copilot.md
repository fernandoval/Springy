# Revisão do Copilot: conectores, reconexão, condições SQL e rotação de tokens

Data: 2026-10-08 — Branch: `development`

Este relatório trata dos seis apontamentos do code review do GitHub Copilot. **Cinco procedem e
foram corrigidos.** O quinto (falta de testes da rotação de tokens) **não procede**: os casos citados
já tinham testes. Mesmo assim, acrescentei dois testes para cobrir caminhos que só eram testados de
forma indireta. Durante a análise, encontrei e corrigi também um defeito que o revisor não apontou
(item 7).

| # | Apontamento | Severidade | Situação |
|---|---|---|---|
| 1 | Host do PostgreSQL lido de variável indefinida | alta | Procede, corrigido |
| 2 | Nova tentativa sem reconectar após perda de conexão | média | Procede, corrigido com uma salvaguarda a mais |
| 3 | Opção `persistent` do MySQL ignorada | média | Procede, corrigido |
| 4 | Detecção de perda de conexão exigia mensagem idêntica | média | Procede, corrigido |
| 5 | Falta de testes da rotação de tokens | média | Não procede (testes já existiam). Dois testes acrescentados |
| 6 | Comparação entre colunas gerava parâmetro a mais | média | Procede, corrigido |
| 7 | `Pdo\Mysql` resolvido no namespace errado (encontrado nesta análise) | — | Corrigido |

## 1. PostgreSQL sempre falhava com "Undefined database server host"

**Procede.** Em [PostgreSQL.php](../../springy/Database/Connectors/PostgreSQL.php) o parâmetro do
construtor se chama `$config`, mas o host era lido de `$settings`, que não existe nesse escopo.
O host sempre ficava vazio, então **nenhuma conexão PostgreSQL funcionava**. Além da exceção, o PHP
emitia um aviso de variável indefinida.

**Correção:** `$this->host = $config['host'] ?? '';`

## 2. Nova tentativa executada na mesma conexão perdida

**Procede.** Uma conexão PDO perdida não se recupera sozinha. Tentar de novo no mesmo objeto PDO
falha outra vez, então a nova tentativa nunca funcionava.

**Correção:** em `executeAgainIfLostConnection()` de
[Connection.php](../../springy/Database/Connection.php), o conector agora é descartado
(`disconnect()`) e recriado (`connect()`) antes de executar a consulta de novo, como sugerido.

**Salvaguarda a mais (não estava na sugestão):** a nova tentativa **não** ocorre quando há uma
transação aberta (`inTransaction()`). Ao reconectar, o servidor descarta a transação. Repetir só a
última instrução numa conexão nova a gravaria fora da transação (em autocommit), e as instruções
anteriores já teriam sido perdidas. Nesse caso, o erro original é relançado para a aplicação tratar.
O Laravel, que inspirou esse código, segue a mesma regra.

## 3. MySQL sempre usava conexão persistente

**Procede.** Em [MySQL.php](../../springy/Database/Connectors/MySQL.php) o parâmetro se chama
`$settings`, mas a opção era lida de `$config`, que não existe nesse escopo. O valor `persistent => false` era ignorado e a
conexão sempre ficava persistente.

**Correção:** `$this->options[PDO::ATTR_PERSISTENT] = $settings['persistent'] ?? true;`

Conferido: com `persistent => false`, a opção resultante é `false`.

## 4. Perda de conexão quase nunca era reconhecida

**Procede.** O arquivo `LostConnectionMessages.txt` contém **trechos** de mensagens
(`server has gone away`, `Broken pipe` etc.), mas `in_array()` exigia que a mensagem inteira da
exceção fosse igual a um deles. O PDO sempre acrescenta prefixos como
`SQLSTATE[HY000]: General error: 2006 MySQL ...`, então a detecção praticamente nunca funcionava, e
por isso a nova tentativa do item 2 também nunca era acionada.

**Correção:** em [LostConnectionDetector.php](../../springy/Database/LostConnectionDetector.php),
a função agora verifica com `str_contains()` se algum trecho aparece na mensagem, como sugerido.

**Teste novo:** [LostConnectionDetectorTest.php](../../tests/Database/LostConnectionDetectorTest.php)
cobre uma mensagem real do PDO com prefixo, uma mensagem exata e um erro comum que não deve ser
reconhecido (tabela inexistente).

## 5. Testes da rotação de tokens "lembrar-me"

**Não procede.** O revisor afirma que só há testes indiretos de `issue()` e `validate()`. Porém,
[RememberTokenManagerTest.php](../../tests/Security/Remember/RememberTokenManagerTest.php) já testa
o `RememberTokenManager` diretamente com um storage em memória (`InMemoryRememberTokenStorage`) e com
um storage controlável que intercala operações concorrentes (`InterleavedRememberTokenStorage`).
Cobertura dos três pontos citados:

| Ponto citado | Teste já existente |
|---|---|
| A rotação consome o token antigo | `testThatRotateReplacesTheToken` |
| O token novo é removido quando a rotação perde a corrida | `testThatRotateDiscardsTheNewTokenWhenTheOldOneIsGone` |
| A identidade é revogada quando o validador não confere | `testThatWrongValidatorRevokesAllIdentityTokens` |

O apontamento provavelmente foi gerado olhando só o teste de integração
(`AuthenticationRememberTest`).

**Mesmo assim, acrescentei dois testes**, porque dois caminhos só eram testados de forma indireta:

- `testThatRotateWithWrongValidatorRevokesTheIdentityWithoutIssuing`: um validador errado passado
  ao `rotate()` (não só ao `validate()`) revoga todos os tokens da identidade e não deixa nenhum
  token novo gravado;
- `testThatOnlyOneOfTwoConcurrentRotationsSucceeds`: duas rotações do mesmo cookie ao mesmo tempo
  resultam em uma única rotação bem-sucedida e em um único token válido, como promete o docblock
  de `rotate()`.

Nenhuma alteração foi necessária em `RememberTokenManager`.

## 6. Comparação entre colunas gerava um parâmetro a mais

**Procede.** Quando `$compareCols` é `true`, o `Condition` escreve o valor como nome de coluna
(`left = right`) e não gera `?`. Mesmo assim, `Conditions::__toString()` adicionava esse valor à
lista de parâmetros. Na execução, o número de parâmetros não batia com o de marcadores.

**Correção:**

- [Condition.php](../../springy/Database/Query/Condition.php) ganhou o método `isValueColumn()`;
- [Conditions.php](../../springy/Database/Query/Conditions.php) só adiciona o valor aos parâmetros
  quando a condição não compara colunas.

**Teste novo:** `testColumnComparisonHasNoParameter` em `tests/Database/Query/ConditionsTest.php.txt`.

> Observação: o diretório `springy/Database/Query/` e seus testes ainda não estão versionados, e os
> testes estão com extensão `.txt`, então o PHPUnit não os executa. Para validar, rodei uma cópia
> temporária do arquivo: 10 testes passaram, incluindo o novo. Ao ativar a suíte (renomeando para
> `.php`), haverá conflito de nome com `tests/DB/ConditionsTest.php`, que declara a mesma classe
> `ConditionsTest` sem namespace. Um dos dois precisará ser renomeado.

## 7. Achado extra: `Pdo\Mysql` resolvido no namespace errado

**Não foi apontado pelo revisor.** Em [MySQL.php](../../springy/Database/Connectors/MySQL.php) o
código fazia:

```php
defined('Pdo\\Mysql::ATTR_INIT_COMMAND')
    ? Pdo\Mysql::ATTR_INIT_COMMAND
    : PDO::MYSQL_ATTR_INIT_COMMAND
```

A string em `defined()` é sempre global. Já o nome `Pdo\Mysql`, sem barra inicial, dentro do
namespace `Springy\Database\Connectors`, é interpretado como
`Springy\Database\Connectors\Pdo\Mysql`. No PHP 8.4 ou superior com `pdo_mysql`, o `defined()`
retorna `true` e a linha seguinte lança "Class not found". **Todo conector MySQL quebraria no
PHP 8.4+.**

**Correção:** `\Pdo\Mysql::ATTR_INIT_COMMAND` (com barra inicial).

## Verificação

- PHPUnit em PHP 8.2 (Docker `php:8.2-cli`), com `LostConnectionDetectorTest` e
  `RememberTokenManagerTest`: 20 testes passaram.
- Suíte completa no mesmo container: 273 testes, 8 erros e 55 pulados. Os 8 erros vêm do ambiente
  e não das alterações: a imagem não tem as extensões `intl` (`Normalizer`, usado por `makeSlug` e
  `removeAccentedChars`) e `pdo_mysql` (`ConnectionTest::testConnectionMySQL`).
- O PHP local é 8.1 e não carrega as classes `readonly` que o projeto usa (o mínimo exigido é 8.2).
  Por isso os testes foram rodados no container.
- Construtores conferidos: o PostgreSQL com `host` gera `pgsql:host=db;port=5432;dbname=x`, e o
  MySQL com `persistent => false` gera `ATTR_PERSISTENT = false`.
- Não houve teste de ponta a ponta da reconexão (item 2) contra um servidor real. O comportamento
  foi conferido pela leitura de `connect()` e `disconnect()`: `disconnect()` remove o conector do
  cache estático e `connect()` cria um novo.
- PHPCS (PSR-12): sem erros nos arquivos alterados. O único aviso é anterior a estas alterações
  (`require_once` em `RememberTokenManagerTest.php`).

## Arquivos alterados

- `springy/Database/Connection.php`
- `springy/Database/Connectors/MySQL.php`
- `springy/Database/Connectors/PostgreSQL.php`
- `springy/Database/LostConnectionDetector.php`
- `springy/Database/Query/Condition.php` (não versionado)
- `springy/Database/Query/Conditions.php` (não versionado)
- `tests/Database/LostConnectionDetectorTest.php` (novo)
- `tests/Database/Query/ConditionsTest.php.txt` (não versionado)
- `tests/Security/Remember/RememberTokenManagerTest.php`
