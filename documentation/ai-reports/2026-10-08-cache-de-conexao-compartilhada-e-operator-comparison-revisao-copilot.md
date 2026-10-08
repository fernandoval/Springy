# Cache de conexão compartilhada e renomeação de `OperatorComparation`

Data: 2026-10-08 — Branch: `development`

## Contexto

O code review do GitHub Copilot fez dois apontamentos:

1. **[Média] Inicializar o cache em conexões já compartilhadas**
   (`springy/Database/Connection.php:57`).
2. **[Baixa] Corrigir a grafia do tipo público `OperatorComparation`**
   (`springy/Database/Query/OperatorComparation.php:13`).

Concordo com os dois e corrigi ambos.

## 1. `$cache` não inicializado em conexões compartilhadas

### Análise

`Connection::$cache` é uma propriedade tipada (`protected array $cache`) sem valor padrão.
A única atribuição ficava em `connect()`, depois do teste que retorna cedo quando o conector
da identidade já está registrado em `self::$conectionIds` com PDO ativo.

Com isso, um segundo `new Connection('x')` para uma identidade já conectada nunca carregava
a configuração de cache. Na primeira chamada a `select(..., cacheLifeTime: > 0)`,
`loadCache()` lia `$this->cache['driver']` e o PHP lançava
`Error: Typed property ... $cache must not be accessed before initialization`.
O erro também ocorre com o driver de cache `none`, porque a leitura da propriedade acontece
antes da comparação com `'memcached'`.

O mesmo vale para as reconexões: quando `checkMissingConnection()` chama `connect()` numa
instância criada sobre um conector compartilhado, a propriedade só era preenchida se a
reconexão de fato acontecesse.

### Correção

A leitura de `config_get('database.cache', ['driver' => 'none'])` saiu de `connect()` e foi
para o construtor, antes da chamada a `connect()`. A configuração de cache é global
(`database.cache`), não depende da identidade nem da conexão. Por isso o construtor é o
lugar certo e `connect()` fica responsável só pela conexão.

### Teste

`ConnectionTest::testThatSharedConnectionLoadsCacheConfiguration` cria duas instâncias de
`Connection` para a mesma identidade SQLite em memória e chama `select()` com
`cacheLifeTime = 60` na segunda. O teste não precisa de MemcacheD.

- Sem a correção: falha com erro em `loadCache()` (`Connection.php:218`).
- Com a correção: passa.

## 2. `OperatorComparation` → `OperatorComparison`

### Análise

"Comparation" não existe em inglês; o termo correto é "comparison". O enum foi introduzido no
commit `3fd3896`, que está apenas na branch `development` e não faz parte de nenhuma tag nem
de `master`. Renomear agora não quebra nenhuma versão publicada. Depois de um release, a
correção exigiria um alias e um ciclo de depreciação.

### Correção

| Antes | Depois |
|---|---|
| `springy/Database/Query/OperatorComparation.php` | `springy/Database/Query/OperatorComparison.php` (via `git mv`) |
| `enum OperatorComparation` | `enum OperatorComparison` |
| `Condition::comparationGeneral()` | `Condition::comparisonGeneral()` |
| `Condition::comparationIn()` | `Condition::comparisonIn()` |
| `Condition::comparationMatch()` | `Condition::comparisonMatch()` |
| "comparation" nos docblocks | "comparison" |

Também atualizei as referências em `Condition.php`, `Conditions.php` e nos testes ainda não
versionados `tests/Database/Query/ConditionTest.php.txt` e `ConditionsTest.php.txt`.
Não sobrou nenhuma ocorrência de "comparation" em `springy/` nem em `tests/`.

Não criei alias de compatibilidade (`class_alias`), porque o nome antigo nunca foi publicado.

## Documentação

`documentation/HISTORY.md` (seção *Unreleased*):

- a entrada das condições de consulta agora cita `OperatorComparison`;
- nova entrada sobre a correção do cache em conexões compartilhadas.

## Verificação

- `php -l` sem erros nos arquivos de `springy/Database/Query/`.
- `phpcs` sem apontamentos em `springy/Database` e `tests/Database/ConnectionTest.php`.
- Os testes `.txt` de `tests/Database/Query/`, copiados como `.php` para um diretório
  temporário, passam (12 testes, 25 asserções) com o novo nome.
- Suíte completa: comparei a lista de falhas antes e depois das mudanças, e ela é idêntica
  (66 falhas e erros que já existiam, causados por serviços indisponíveis neste ambiente, como
  MySQL: `testConnectionMySQL` falha com `Database name undefined`). O novo teste passa e as
  mudanças não introduziram nenhuma regressão.

## Arquivos alterados

- `springy/Database/Connection.php`
- `springy/Database/Query/OperatorComparation.php` → `springy/Database/Query/OperatorComparison.php`
- `springy/Database/Query/Condition.php`
- `springy/Database/Query/Conditions.php`
- `tests/Database/ConnectionTest.php`
- `tests/Database/Query/ConditionTest.php.txt` e `ConditionsTest.php.txt` (não versionados)
- `documentation/HISTORY.md`
