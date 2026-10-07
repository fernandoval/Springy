# Tokens revogáveis para o "Lembrar-me"

Data: 2026-10-07 — Branch: `development`

## Problema

O `Springy\Security\Authentication` gravava no cookie de "lembrar-me" o **ID do usuário em texto puro**
(`Cookie::set($key, $user->getId(), ...)`). Na requisição seguinte sem sessão, o `rememberSession()`
fazia `loginWithId($cookie)`. Consequências:

1. **Não havia como invalidar o lembrete.** Excluir a sessão não adiantava: o cookie recriava a
   sessão no próximo acesso. A única saída era esperar os 60 dias do cookie.
2. **Falha de segurança grave:** qualquer pessoa que criasse no navegador um cookie com o nome da
   chave de identidade e o ID de outro usuário (`42`, um UUID etc.) entrava como esse usuário.
3. `destroyUserData()` chamava `Cookie::set(..., time() - 3600, ...)`, mas `Cookie::set()` soma
   `time()` ao prazo. O cookie de "remoção" recebia uma validade de décadas no futuro.

## Solução

Padrão **seletor + validador** com rotação a cada uso:

- O cookie guarda `seletor:validador` (12 + 32 bytes aleatórios em hexadecimal). Ele **não contém
  o ID do usuário**.
- O servidor guarda o seletor, o ID da identidade, a data de expiração e **somente o hash SHA-256
  do validador**. Quem vazar o armazenamento não consegue montar um cookie válido.
- A comparação do validador usa `hash_equals()` (tempo constante).
- **Rotação:** a cada restauração de sessão pelo cookie, o token usado é revogado e outro é emitido.
- **Detecção de roubo:** um seletor conhecido com validador errado indica que o cookie foi copiado e
  já usado por outra pessoa (o token já tinha rotacionado). Nesse caso, **todos os tokens daquela
  identidade são revogados**.
- **Invalidação explícita:** `revoke()` revoga um cookie e `revokeAllFor($id)` revoga todos os
  dispositivos de um usuário. Isso atende ao requisito: o administrador ou a aplicação pode forçar
  um novo logon a qualquer momento.

### Componentes (`springy/Security/Remember/`)

| Arquivo | Responsabilidade |
|---|---|
| `RememberTokenStorageInterface` | Contrato do driver: `save`, `findBySelector`, `delete`, `deleteAllByIdentity`. |
| `RememberTokenManager` | Regras de negócio: emitir, validar (expiração e roubo) e revogar. |
| `RememberTokenCredential` | Value object do cookie (gera, faz parse e serializa `seletor:validador`). |
| `RememberToken` | Value object do registro no servidor (hash, identidade, expiração). |
| `RedisRememberTokenStorage` | Driver Redis/Valkey (extensão `phpredis`). |
| `MemcachedRememberTokenStorage` | Driver MemcacheD (extensão `memcached`). |
| `DatabaseRememberTokenStorage` | Driver de banco de dados via `Springy\Database\Connection`. |
| `remember_tokens_create_table.sql` | DDL da tabela para MySQL/MariaDB. |
| `InvalidRememberTokenException` | Cookie recusado (malformado, inexistente, expirado, validador divergente). |
| `RememberTokenStorageException` | Falha de infraestrutura no driver. |

Os drivers recebem o cliente já configurado (`Redis`, `Memcached`, `Connection`) por injeção no
construtor. Assim o desenvolvedor controla a conexão (TLS, cluster, sentinel, persistência), e os
drivers podem ser testados.

### Como cada driver revoga "todos os tokens de um usuário"

- **Redis/Valkey:** cada token fica em uma chave com TTL igual ao seu prazo. Um `SET` por identidade
  indexa os seletores. `deleteAllByIdentity` apaga o índice e as chaves. A gravação é feita em `MULTI`.
- **MemcacheD:** o Memcached não lista chaves. Cada identidade tem uma "geração" aleatória gravada
  dentro dos seus tokens, e revogar tudo apaga a geração. Um token cuja geração não confere é
  descartado. Se o Memcached despejar a geração por falta de memória, os tokens também são
  invalidados, ou seja, **a falha é sempre fechada** (o usuário só precisa logar de novo). As
  expirações são enviadas como timestamp absoluto, porque o Memcached interpreta TTLs acima de
  30 dias como datas de 1970.
- **Banco de dados:** `DELETE ... WHERE identity_id = ?`. O banco não expira linhas sozinho, então
  existe `deleteExpired(): int` para ser agendado (cron/comando).

## Alterações em `Authentication`

- O construtor agora é `__construct(?AuthDriverInterface $driver = null, ?RememberTokenManager $rememberTokens = null)`.
- `login(..., remember: true)` emite um token e grava o cookie com o tempo de vida do gerenciador.
- `rememberSession()` valida o cookie e rotaciona o token. Se o cookie for inválido, revogado, de
  usuário removido ou no formato antigo (ID puro), ele é apagado e o usuário fica deslogado.
- `logout()` revoga o token do cookie atual.
- Novo método `logoutFromAllDevices()`: revoga todos os tokens do usuário e faz o logout.
- `loginWithId()` passou a checar `$user->isLoaded()`. Antes ele testava `if ($user)`, que é sempre
  verdadeiro para objetos, e uma identidade vazia podia ser "logada".
- Corrigida a expiração do cookie de remoção (`-3600` em vez de `time() - 3600`).

## Configuração

Nova seção `remember_me` em `conf/system.php`:

```php
'remember_me' => [
    'lifetime' => 5184000, // 60 dias
    'redis' => ['host', 'port', 'password', 'database', 'timeout', 'prefix'], // via env REDIS_*
    'memcached' => ['address', 'port', 'prefix'],                            // via env MEMCACHED_*
    'database' => ['connection' => null, 'table' => '_remember_tokens'],
],
```

O driver é escolhido no binding `security.remember.storage` em `app/helpers.php`, da mesma forma
que o hasher (`security.hasher`) e o driver de autenticação (`user.auth.driver`). O padrão do
projeto de exemplo é o banco de dados.

### Usando Redis/Valkey

```php
$app->bind('security.remember.storage', function () {
    $config = config_get('system.remember_me.redis');
    $redis = new Redis();
    $redis->connect($config['host'], $config['port'], $config['timeout']);

    if ($config['password'] !== '') {
        $redis->auth($config['password']);
    }

    $redis->select($config['database']);

    return new Springy\Security\Remember\RedisRememberTokenStorage($redis, $config['prefix']);
});
```

### Usando MemcacheD

```php
$app->bind('security.remember.storage', function () {
    $config = config_get('system.remember_me.memcached');
    $memcached = new Memcached();
    $memcached->addServer($config['address'], $config['port']);

    return new Springy\Security\Remember\MemcachedRememberTokenStorage($memcached, $config['prefix']);
});
```

### Usando banco de dados

Crie a tabela com `springy/Security/Remember/remember_tokens_create_table.sql`. `expires_at` é um
timestamp Unix, para evitar ambiguidade de fuso entre PHP e banco. Agende a limpeza:

```php
app('security.remember.storage')->deleteExpired();
```

### Driver próprio

Basta implementar `RememberTokenStorageInterface`. A classe abstrata de testes
`tests/Security/Remember/RememberTokenStorageTestCase.php` pode ser estendida para validar o novo
driver contra o mesmo contrato dos drivers oficiais.

### Forçando novo logon

```php
app('security.remember.manager')->revokeAllFor((string) $user->getId()); // ex.: troca de senha, bloqueio
app('user.auth.manager')->logoutFromAllDevices();                        // o próprio usuário
```

Isso invalida os cookies de "lembrar-me". Sessões já abertas continuam valendo até expirarem ou
serem destruídas, como antes.

## Quebras de compatibilidade

1. **Cookies antigos (ID puro) deixam de funcionar.** No deploy, quem estava "lembrado" precisará
   logar novamente uma vez. É intencional: aceitar esses cookies manteria a falha de falsificação.
2. **Sem `RememberTokenManager`, o "lembrar-me" fica desativado** (`$remember` é ignorado). Projetos
   que constroem `new Authentication($driver)` precisam passar o gerenciador para manter o recurso.
3. Com o driver de banco, a tabela `_remember_tokens` precisa existir.

## Decisões para revisão

- **"Lembrar-me" sem gerenciador é ignorado em silêncio.** A alternativa seria lançar exceção em
  `login(..., true)`. Isso é mais explícito, mas derrubaria o login de projetos que atualizarem o
  framework sem configurar o binding. Escolhi não quebrar o login. É fácil mudar se preferir falhar
  alto.
- **Nome do cookie** continua sendo `getIdentitySessionKey()`, como antes, para não exigir mudanças
  nas identidades existentes.
- **ID da identidade é tratado como `string`** nos tokens (`IdentityInterface::getId()` retorna
  `mixed`). Inteiros e UUIDs funcionam; o valor volta como string para `getIdentityById()`.
- O `RememberTokenManager` usa o relógio do sistema (`new DateTimeImmutable()`). Não introduzi uma
  abstração de relógio porque os testes não precisaram dela.

## Testes

Novos testes em `tests/Security/Remember/` e `tests/Security/AuthenticationRememberTest.php`:

- `RememberTokenCredentialTest`: formato, unicidade, round-trip, hash e rejeição de 9 formatos
  malformados (incluindo o cookie legado com ID puro).
- `RememberTokenTest`: validador, expiração, serialização.
- `RememberTokenManagerTest`: emissão (só o hash é armazenado e o ID não aparece no cookie),
  validação, revogação individual e global, expiração e detecção de roubo.
- `RedisRememberTokenStorageTest`, `MemcachedRememberTokenStorageTest`,
  `DatabaseRememberTokenStorageTest`: o mesmo contrato (`RememberTokenStorageTestCase`) executado
  contra **Valkey 8, Memcached 1.6 e MySQL 8 reais**, mais testes específicos de cada driver (TTL,
  tokens acima de 30 dias no Memcached, geração despejada, servidor inacessível, `deleteExpired`).
- `AuthenticationRememberTest`: fluxo completo, em processos isolados por causa do estado estático
  de `Session`/`Cookie`. Cobre emissão no login, restauração com rotação, cookie revogado forçando
  novo logon, cookie legado recusado, usuário removido, `logout` e `logoutFromAllDevices`.

O script `tests/bin/run` agora também sobe containers `valkey/valkey:8-alpine` e
`memcached:1.6-alpine` e passa `REDIS_HOST`/`MEMCACHED_HOST` aos testes. Sem essas variáveis, os
testes dos drivers são marcados como *skipped*.

Resultado (`tests/bin/run`): **OK (245 tests, 479 assertions) em PHP 8.2.34, 8.3.30, 8.4.18 e
8.5.11**, com `phpcs` (PSR-12) sem erros nem avisos.

Observação: em uma das execuções, o MySQL demorou mais que os 30s de espera do script e a execução
abortou antes dos testes. Na repetição passou. Se isso for frequente, vale aumentar o `TIMEOUT` do
script.

## Arquivos alterados

- Novos: `springy/Security/Remember/*` (9 classes/interfaces + SQL), `tests/Security/Remember/*`,
  `tests/Security/AuthenticationRememberTest.php`.
- Alterados: `springy/Security/Authentication.php`, `conf/system.php`, `app/helpers.php`,
  `tests/bin/run`, `documentation/HISTORY.md`.
