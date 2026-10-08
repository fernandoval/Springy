# Revisão do Copilot: nova tentativa de escritas e Valkey em cluster

Data: 2026-10-08 — Branch: `development`

Este relatório trata dos dois apontamentos de severidade alta do code review do GitHub Copilot.
**Os dois procedem e foram corrigidos.** O segundo era mais amplo do que o revisor descreveu: além
da revogação, a gravação e a remoção de tokens também falhavam em cluster.

| # | Apontamento | Severidade | Situação |
|---|---|---|---|
| 1 | Nova tentativa automática pode duplicar escritas | alta | Procede, corrigido |
| 2 | Script de revogação falha com CROSSSLOT no Valkey em cluster | alta | Procede, corrigido. O problema era maior: `save()` e `delete()` também falhavam |

## 1. Nova tentativa automática pode duplicar escritas

**Procede.** Na revisão anterior, `executeAgainIfLostConnection()` em
[Connection.php](../../springy/Database/Connection.php) passou a reconectar e executar de novo
**qualquer** instrução após perda de conexão (exceto dentro de transação). Para escritas, isso é
inseguro. Se a conexão cai depois que o servidor aplicou o `INSERT`/`UPDATE`, mas antes de a resposta
chegar, o cliente não tem como saber se a escrita aconteceu. Repetir pode aplicá-la duas vezes ou
transformar um sucesso em erro de chave duplicada.

O Laravel, que inspirou esse código, repete qualquer instrução. Mesmo assim, a crítica é correta:
o risco de escrita duplicada é real e silencioso.

**Correção:**

- Após detectar perda de conexão (fora de transação), a conexão é **sempre descartada**
  (`disconnect()`), para que a próxima instrução abra uma conexão nova.
- Só instruções **somente leitura** são repetidas. O novo método `isReadOnlyQuery()` ignora
  espaços, comentários (`/* */`, `--`, `#`) e parênteses iniciais e aceita apenas `SELECT`, `SHOW`,
  `DESCRIBE` e `DESC`.
- Ficam de fora, de propósito:
  - `WITH`, porque uma CTE pode modificar dados (`WITH x AS (DELETE ... RETURNING *)`);
  - `SELECT ... INTO`, porque cria tabela no PostgreSQL ou grava arquivo no MySQL;
  - `EXPLAIN`, porque `EXPLAIN ANALYZE` executa a instrução analisada.
- Para escritas, o erro original é relançado e `getError()` continua com a mensagem. A aplicação
  decide se a operação pode ser repetida.

**Limitação conhecida:** um `SELECT` que chama funções com efeito colateral (`nextval()`,
`GET_LOCK()`, funções armazenadas) é considerado leitura. Isso não pode ser detectado pelo texto da
instrução. O método é `protected`, então uma subclasse pode restringi-lo.

**Mudança de comportamento:** em processos longos (workers), a primeira escrita após o servidor
derrubar uma conexão ociosa (`MySQL server has gone away`) passa a lançar exceção em vez de ser
repetida em silêncio. A instrução seguinte já usa uma conexão nova. Nesse caso específico a escrita
quase certamente não chegou ao servidor, mas o PDO não permite distinguir esse caso do caso ambíguo
com segurança. Por isso optei pela regra conservadora.

**Testes novos:** [LostConnectionRetryTest.php](../../tests/Database/LostConnectionRetryTest.php),
com SQLite em memória e uma conexão que simula a perda:

- uma leitura é repetida numa conexão nova e retorna o resultado;
- uma escrita é executada uma única vez, lança a exceção, deixa a conexão descartada, e a instrução
  seguinte funciona;
- dentro de transação nada é repetido;
- a classificação de instruções cobre 9 leituras e 12 não leituras.

Contra o código anterior, esses testes falham (a escrita era executada duas vezes).

## 2. Valkey/Redis em cluster (AWS ElastiCache Serverless)

**Procede, e o problema era maior.** Em cluster, cada chave pertence a um *hash slot*. Comandos,
transações (`MULTI`/`EXEC`) e scripts com várias chaves só funcionam se todas estiverem no mesmo
slot. O ElastiCache Serverless sempre opera em modo cluster. O desenho anterior de
[RedisRememberTokenStorage.php](../../springy/Security/Remember/RedisRememberTokenStorage.php) usava
uma chave por token (`token:<selector>`) e um conjunto por identidade (`identity:<id>`), em slots
diferentes. Por isso falhavam:

- `deleteAllByIdentity()`: o script Lua acessava chaves de token não declaradas em `KEYS`, como
  apontado;
- `save()`: o `MULTI` gravava a chave do token e o índice da identidade, em slots diferentes;
- `delete()`: o `MULTI` removia a chave do token e o item do índice, também em slots diferentes.

Reproduzi o problema com um Valkey 8 de um nó em modo cluster. Com o código antigo, 14 dos 19 testes
do driver falharam com `Redis refused to save the remember token` (cliente `Redis`), e 11 dos 15
testes de contrato falharam com `Error processing EXEC across the cluster` (cliente `RedisCluster`).
**Na prática, o driver não funcionava em cluster de jeito nenhum**, não só na revogação.

**Correção:** novo layout de chaves, em que **cada comando ou transação toca uma única chave**:

| Chave | Tipo | Conteúdo | TTL |
|---|---|---|---|
| `<prefixo>tokens:<identityId>` | hash | campo = selector, valor = token em JSON | o do token mais novo |
| `<prefixo>selector:<selector>` | string | `identityId` (ponteiro) | o do token |

| Operação | Comandos |
|---|---|
| `save()` | `MULTI HSET + EXPIRE` no hash (mesma chave) e, depois, `SETEX` do ponteiro |
| `findBySelector()` | `GET` do ponteiro e `HGET` no hash da identidade |
| `delete()` | `HDEL` no hash (atômico: só uma chamada concorrente recebe 1) e, depois, `DEL` do ponteiro |
| `deleteAllByIdentity()` | `MULTI HKEYS + DEL` no hash (mesma chave) e, depois, `DEL` de cada ponteiro |

As garantias do contrato continuam valendo:

- **Revogação atômica:** revogar é um único `DEL` do hash. Um token cujo campo sumiu do hash não é
  encontrado, mesmo que o ponteiro ainda exista. Se a revogação cair entre a gravação do campo e a do
  ponteiro, o ponteiro aponta para nada e o token fica revogado. Há um teste específico para esse
  estado (`testThatTokenIsRevokedWhenTheIdentityHashIsGone`).
- **Consumo único:** o `HDEL` retorna 1 para uma única chamada concorrente, o que a rotação exige.
- **Limpeza dos ponteiros:** é só higiene. Falhas são ignoradas, porque o ponteiro expira junto com
  o token. Cada ponteiro é removido por um comando próprio, pois estão em slots diferentes.

O script Lua deixou de existir. Não usei *hash tags* (`{...}`): elas exigiriam conhecer a identidade
na busca por selector, e isso não é possível.

**Outras mudanças no driver:**

- O construtor aceita `Redis|RedisCluster`, para quem conecta ao cluster com o cliente de cluster
  do phpredis.
- Passa a capturar `RedisClusterException`, que **não** herda de `RedisException` (herda de
  `RuntimeException`). Sem isso, erros do cliente de cluster escapariam sem virar
  `RememberTokenStorageException`.

**Compatibilidade:** os nomes das chaves mudaram (`token:`/`identity:` → `selector:`/`tokens:`).
O driver ainda não foi publicado em nenhuma versão (nenhuma tag contém o commit que o criou), então
não há dados a migrar.

**Testes:**

- [RedisRememberTokenStorageTest.php](../../tests/Security/Remember/RedisRememberTokenStorageTest.php)
  foi ajustado ao novo layout e ganhou dois testes: a remoção de todas as chaves por
  `deleteAllByIdentity()` e o caso de revogação entre as duas gravações.
- Novo [RedisClusterRememberTokenStorageTest.php](../../tests/Security/Remember/RedisClusterRememberTokenStorageTest.php):
  roda todos os testes de contrato com o cliente `RedisCluster` contra `REDIS_CLUSTER_HOST`
  (é pulado sem essa variável).
- [tests/bin/run](../../tests/bin/run) e o workflow
  [tests.yml](../../.github/workflows/tests.yml) agora sobem um Valkey de um nó em modo cluster. Esse
  nó já rejeita operações entre slots (CROSSSLOT). No GitHub Actions o nó é iniciado num passo
  `docker run`, porque *service containers* não aceitam argumentos de comando.

## Verificação

- Suíte completa no Docker (`springy-test:8.2` e `springy-test:8.5`) com MySQL 8.0, Memcached,
  Valkey comum e Valkey em cluster: **296 testes, 626 asserções, todos passaram, nenhum pulado**.
- Os testes Redis comuns também rodaram contra o nó em cluster, com o cliente `Redis`: todos
  passaram.
- O código antigo do driver falhou nos mesmos testes em cluster, e o código antigo de `Connection`
  falhou nos novos testes de nova tentativa. Ou seja, os testes detectam os dois defeitos.
- PHPCS (PSR-12): nenhum erro nos arquivos alterados. Os avisos de `require_once` nos testes do
  Remember seguem o padrão já existente.
- YAML do workflow e sintaxe do `tests/bin/run` validados. O workflow ainda não rodou no GitHub
  Actions.
- **Não testado:** ElastiCache Serverless real e cluster com vários nós. O nó único em cluster
  reproduz a regra de CROSSSLOT, mas não os redirecionamentos `MOVED` entre nós. Esses são tratados
  pelo `RedisCluster` do phpredis.

## Arquivos alterados

- `springy/Database/Connection.php`
- `springy/Security/Remember/RedisRememberTokenStorage.php`
- `tests/Database/LostConnectionRetryTest.php` (novo)
- `tests/Security/Remember/RedisRememberTokenStorageTest.php`
- `tests/Security/Remember/RedisClusterRememberTokenStorageTest.php` (novo)
- `tests/bin/run`
- `.github/workflows/tests.yml`
- `documentation/HISTORY.md`
