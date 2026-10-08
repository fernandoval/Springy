# Revisão do Copilot: `getPdo()` não anulável impedia a reconexão após falha

Data: 2026-10-08 — Branch: `development`

**O apontamento procede e foi corrigido.** A correção sugerida (`?PDO`) foi aplicada. Também fiz
dois ajustes em `Connection` para que um conector sem conexão não fique no registro estático e para
que todas as verificações de "está conectado?" usem o mesmo critério.

| Item | Situação |
|---|---|
| `Connector::getPdo()` com retorno `PDO` lançava `TypeError` quando `$pdo` era `null` | Procede, corrigido como sugerido |
| `Connection::connect()` registrava o conector antes de conectar | Ajuste extra: o registro agora ocorre só depois do sucesso |
| `Connection::getPdo()` e `checkMissingConnection()` não tratavam conector sem PDO | Ajuste extra: ambos usam `isConnected()` |

## O defeito

Em [Connection.php](../../springy/Database/Connection.php), `connect()` fazia:

```php
self::$conectionIds[$this->identity] = $connector;
$connector->connect();   // pode lançar exceção
```

Quando `$connector->connect()` falhava (servidor fora do ar, tentativas esgotadas), o conector já
estava no registro com `$pdo === null`. Na tentativa seguinte, `connect()` verificava o cache com
`getPdo() !== null`. Como [Connector.php](../../springy/Database/Connectors/Connector.php) declarava
`getPdo(): PDO` e a propriedade é `PDO|null`, o PHP lançava:

```
TypeError: Springy\Database\Connectors\Connector::getPdo(): Return value must be of type PDO, null returned
```

A reconexão nunca acontecia. Depois da primeira falha, **todas as `Connection` daquela identidade
no processo** ficavam quebradas, inclusive as criadas depois, porque o registro é estático. Isso
afeta principalmente processos longos (workers, filas), onde uma queda momentânea do banco não
deveria exigir reinício. `isConnected()` também lançava `TypeError` nesse estado em vez de retornar
`false`.

Reproduzi o defeito com um teste antes da correção: o erro aparece exatamente em
`Connection.php:268`, como descrito no apontamento.

## Correções

1. **`Connector::getPdo(): ?PDO`** (sugestão do revisor). O retorno agora corresponde à propriedade
   e às verificações `!== null` e `instanceof PDO` que já existiam em `Connection`. Antes de
   `connect()`, o getter retorna `null`, que é um estado legítimo.

2. **Registro só após conexão bem-sucedida** (`Connection::connect()`). As duas linhas foram
   invertidas: o conector só entra em `$conectionIds` depois de `$connector->connect()` retornar.
   Uma falha não deixa mais um conector incompleto no registro. A correção 1 sozinha já resolve o
   `TypeError`; esta remove a origem do estado inconsistente.

3. **`Connection::getPdo()` e `checkMissingConnection()` usam `isConnected()`**.
   - `getPdo()` (protegido, retorno `PDO`) só conectava quando a identidade não estava no registro.
     Com a correção 1, um conector registrado sem PDO faria esse método lançar `TypeError`. Agora ele
     também conecta nesse caso.
   - `checkMissingConnection()` fazia `is_null($this->getPdo())` sobre um método que retorna `PDO`,
     uma verificação que nunca podia ser verdadeira. Agora ele usa o mesmo critério.

Nenhuma assinatura pública mudou, exceto `Connector::getPdo()`, que passou a aceitar `null`.
Nenhum código do projeto nem da documentação depende do retorno não anulável. Subclasses externas
que sobrescrevam `getPdo(): PDO` continuam compatíveis, porque um retorno mais restrito é permitido.

## Testes

Novo arquivo [FailedConnectionTest.php](../../tests/Database/FailedConnectionTest.php), que usa um
conector SQLite em memória cujas primeiras tentativas falham conforme o teste pede. O teste não
precisa de servidor.

- `testConnectorPdoIsNullBeforeConnecting`: `getPdo()` retorna `null` antes de conectar.
- `testReconnectsAfterFailedConnection`: depois de uma falha no construtor e de outra em
  `connect()`, `isConnected()` retorna `false` sem lançar exceção, a próxima tentativa conecta e uma
  consulta é executada.

## Verificação

- Sem a correção, os dois testes novos falham com o `TypeError` descrito. Com ela, passam.
- Suíte completa via `tests/bin/run 8.4` (Docker, PHP 8.4.18, MySQL 8.0, Valkey e Memcached):
  **275 testes passaram, 559 asserções**, e o PHPCS não apontou erros.
- No PHP local (8.1), os erros restantes já existiam e vêm do ambiente: classes `readonly`, que
  exigem PHP 8.2 (o mínimo do projeto), e `ConnectionTest`, que precisa de configuração do MySQL.

## Arquivos alterados

- `springy/Database/Connectors/Connector.php`
- `springy/Database/Connection.php`
- `tests/Database/FailedConnectionTest.php` (novo)
