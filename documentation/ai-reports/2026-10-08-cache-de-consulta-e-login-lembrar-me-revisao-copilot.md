# Revisão do Copilot: cache de consulta, logon com "lembrar-me" e storage em banco

Data: 2026-10-08 — Branch: `development`

**Os três apontamentos procedem e foram corrigidos.** Uma ressalva sobre o primeiro: o Copilot
diz que o problema "também aparece na linha 295". No código atual, essa linha fica dentro de
`connect()` e não tem chamada a `closeCursor()`. A única ocorrência redundante era a de
`saveCache()`.

| # | Apontamento | Situação |
|---|---|---|
| 1 | `closeCursor()` redundante depois de `getAll()` em `saveCache()` | Procede, corrigido |
| 2 | Falha do storage no logon deixa o usuário autenticado pela metade | Procede, corrigido |
| 3 | `DatabaseRememberTokenStorage` deixa vazar exceções de PDO | Procede, corrigido |

## 1. `closeCursor()` redundante em `Connection::saveCache()`

**O defeito.** `getAll()` já fecha o cursor e troca `$this->statement` pelo array de linhas.
Depois disso, `saveCache()` chamava `$this->statement->closeCursor()` sobre um array. Isso lança
`Error` a cada consulta que não estava no cache. O `catch (Throwable)` escondia o erro e chamava
`debug()` com a consulta e a mensagem "Call to a member function closeCursor() on array". O
resultado da consulta saía certo, porque o array já estava em `$this->statement` e o
`Memcached::set()` já tinha rodado. O efeito era só a mensagem falsa na janela de debug. O defeito
existe desde a criação da classe (`32b123c`).

**Correção.** Removi a chamada a `closeCursor()` e também a atribuição `$this->statement = $rows`,
que repetia o que `getAll()` já faz. Um comentário curto registra esse comportamento.

## 2. Logon parcial quando o storage do "lembrar-me" falha

Esse é o ponto que ficou em aberto no relatório
[2026-10-08-falhas-do-storage-lembrar-me-revisao-copilot.md](2026-10-08-falhas-do-storage-lembrar-me-revisao-copilot.md).

**O defeito.** `login($user, true)` gravava o usuário na sessão PHP e só depois emitia o token.
Com o storage fora do ar, `issue()` lançava `RememberTokenStorageException`, e a tela de login
mostrava um erro. Mas a sessão já estava gravada, então na requisição seguinte o usuário aparecia
autenticado. A mesma falha tinha dois resultados contraditórios.

**Correção.** Segui a primeira opção do Copilot: o token é emitido **antes** de qualquer
alteração de estado. Se `issue()` falhar, a exceção sai de `login()` sem tocar em
`$this->user`, na sessão ou no cookie. O logon é atômico: ou acontece por inteiro, ou não
acontece.

Escolhi essa opção em vez de concluir o logon sem "lembrar-me" porque:

- ela não descarta sem aviso a escolha do usuário. Quem chama `attempt()` ou `login()` recebe a
  exceção e decide o que fazer, por exemplo tentar de novo sem `$remember` e avisar o usuário;
- ela não muda o contrato: o logon com "lembrar-me" já falhava de forma explícita. Só deixou de
  ficar pela metade;
- é o mesmo comportamento de `logoutAndRevokeRememberTokens()`, que também informa a falha a
  quem pediu uma operação que depende do storage.

O `@throws RememberTokenStorageException` foi documentado em `login()`. O método protegido
`rememberUser()` deixou de ser necessário e foi removido. Ele ainda não saiu em nenhuma versão
publicada (a v4.6.0 não o tem), então a remoção não afeta subclasses de usuários.

Se a sessão falhar depois que o token foi emitido, esse token fica órfão no storage e expira
sozinho, porque o cliente nunca recebeu o cookie.

## 3. Exceções do banco em `DatabaseRememberTokenStorage`

**O defeito.** A interface `RememberTokenStorageInterface` promete
`RememberTokenStorageException` em toda falha, e `Authentication` só trata esse tipo. Os drivers
de Redis e Memcached já convertiam as exceções, mas o driver de banco deixava passar
`PDOException` e `SpringyException`. Com o banco fora do ar, a restauração da sessão no
construtor de `Authentication` virava um erro 500. Era o mesmo defeito já corrigido para o
Redis, só que com o driver de banco.

**Correção.** Cada operação (`save`, `findBySelector`, `delete`, `deleteAllByIdentity` e
`deleteExpired`) agora captura `Throwable` e lança `RememberTokenStorageException` com a exceção
original em `previous`. O padrão segue o do `RedisRememberTokenStorage`, com um método privado
`createFailure()`.

Escolhi capturar `Throwable` em vez de uma lista de tipos porque `Connection` pode lançar
`PDOException`, `SpringyException` (driver ausente, conexão perdida) e repassar qualquer erro de
`executeQuery()`. O `LazyRememberTokenStorage` usa o mesmo critério. A chamada a `enclose()`
também fica dentro do `try`, porque ela pode abrir a conexão.

`deleteExpired()` não faz parte da interface, mas também foi coberto e documentado, para manter o
mesmo contrato em todo o driver.

## Testes

- Novo [ConnectionCacheTest.php](../../tests/Database/ConnectionCacheTest.php): faz uma consulta
  com cache em SQLite e MemcacheD e confere que nada foi enviado ao `Debug`. Depois apaga as
  linhas e confere que a segunda consulta vem do cache. A consulta usa um parâmetro único para não
  encontrar o cache de execuções anteriores.
- [AuthenticationRememberTest.php](../../tests/Security/AuthenticationRememberTest.php):
  `testThatLoginWithRememberDuringStorageOutageDoesNotAuthenticate` confere que a exceção é
  lançada e que `check()`, a sessão e o cookie continuam vazios.
- [DatabaseRememberTokenStorageTest.php](../../tests/Security/Remember/DatabaseRememberTokenStorageTest.php):
  `testThatDatabaseFailuresAreWrappedInStorageException` aponta o driver para uma tabela
  inexistente e confere, em cada uma das cinco operações, que sai
  `RememberTokenStorageException` com `PDOException` em `previous`.

Os três testes novos falham com o código anterior (1 erro e 2 falhas de asserção) e passam com as
correções.

Resultado: **OK (334 tests, 706 assertions) em PHP 8.2, 8.3, 8.4 e 8.5.**

Durante a validação, o Docker Hub não respondia (timeout ao resolver `composer:latest`), e o
`tests/bin/run` não conseguia reconstruir as imagens. Rodei a suíte com as imagens
`springy-test:*` já existentes, montando `springy/` e `tests/` do diretório de trabalho por cima
do código copiado na imagem. Os serviços (MySQL, Valkey, Valkey Cluster e MemcacheD) e as
variáveis de ambiente são os mesmos do script. Vale rodar `tests/bin/run` de novo quando o
registro voltar.

O `phpcs` (PSR-12) não aponta nada nos arquivos alterados. Nos testes aparece só o aviso de
*side effect* do `require_once`, que já existia.

## Arquivos

- Alterado: `springy/Database/Connection.php`.
- Alterado: `springy/Security/Authentication.php`.
- Alterado: `springy/Security/Remember/DatabaseRememberTokenStorage.php`.
- Novo: `tests/Database/ConnectionCacheTest.php`.
- Alterado: `tests/Security/AuthenticationRememberTest.php`.
- Alterado: `tests/Security/Remember/DatabaseRememberTokenStorageTest.php`.
- Alterado: `documentation/HISTORY.md`.
