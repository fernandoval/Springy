# Revisão do Copilot: "lembrar-me" e construtor de `Authentication`

Data: 2026-10-07 — Branch: `development`

Este relatório trata das duas falhas que o code review do GitHub Copilot apontou na implementação
descrita em [2026-10-07-remember-me-tokens.md](2026-10-07-remember-me-tokens.md). **As duas
procedem e foram corrigidas.** Não discordo de nenhuma delas. Só a solução da primeira é diferente
da sugestão principal do revisor, conforme explicado abaixo.

## 1. Login automático podia anular um "revogar tudo" concorrente (alta)

### Diagnóstico: procede

O `rememberSession()` fazia três passos separados:

1. `validate($cookie)`: lê o token T e confere o validador;
2. `revoke($cookie)`: apaga T;
3. `loginWithId($id, true)`: emite um token novo T'.

Nada coordenava esses passos com `revokeAllFor()`. A sequência abaixo era possível:

| Requisição A (cookie T) | Administrador |
|---|---|
| `validate(T)`: válido | |
| | `revokeAllFor(U)`: apaga T e retorna sucesso |
| `revoke(T)`: não apaga nada e ninguém percebe | |
| `issue(U)`: grava T' | |

Resultado: o administrador recebia a confirmação de sucesso, mas T' continuava válido. Como T' é
rotacionado a cada uso, o "lembrar-me" podia durar indefinidamente. A janela inclui a consulta do
usuário no banco (`getIdentityById`), então não é só teórica.

O mesmo defeito permitia que **duas requisições com o mesmo cookie** rotacionassem o token ao mesmo
tempo, gerando dois tokens válidos a partir de um só.

### Solução: rotação atômica por "grava o novo, consome o antigo"

O revisor sugeriu "uma geração/época por identidade **ou outro mecanismo de rotação atômica**".
Escolhi a segunda opção, porque ela funciona igual nos três drivers sem mudar o esquema da tabela
nem o formato dos dados no Redis. (O driver Memcached já usa uma geração internamente, e ela
continua lá.)

Novo método `RememberTokenManager::rotate(string $cookieValue): array{string, RememberTokenCredential}`:

1. valida o cookie e obtém o token T;
2. **grava primeiro** o token novo T';
3. **consome** T com `delete()`, que agora retorna se removeu um token válido;
4. se T já não existia (revogado ou consumido por outra requisição), **apaga T'** e lança
   `InvalidRememberTokenException::notFound()`.

Por que isso fecha a corrida: T' é gravado **antes** de T ser consumido. Há dois casos:

- **`revokeAllFor` termina antes do passo 3.** T já foi apagado, o consumo falha e T' é descartado.
  O login automático é recusado.
- **`revokeAllFor` começa depois do passo 3.** T' já estava gravado e é apagado junto com os outros
  tokens.

Em nenhum dos casos o token sobrevive à revogação. A sessão aberta pela requisição A no segundo caso
continua valendo. Isso já era assim e está documentado: o "revogar tudo" invalida os cookies de
"lembrar-me", não as sessões já abertas. A requisição A é equivalente a um login feito pouco antes
da revogação.

### Requisito novo no contrato do driver

`RememberTokenStorageInterface` passou a exigir:

- `delete(string $selector): bool` retorna `true` **somente** se esta chamada removeu um token
  válido. Se duas chamadas concorrentes removerem o mesmo token, no máximo uma recebe `true`.
- `deleteAllByIdentity()` deve ser atômico em relação a `save()`: todo token gravado antes da
  chamada começar deve estar revogado quando ela retornar.

Ajustes em cada driver:

| Driver | `delete()` | `deleteAllByIdentity()` |
|---|---|---|
| Banco de dados | `execute()` e retorna `affectedRows > 0` | Já era atômico (um único `DELETE`) |
| Redis/Valkey | Retorna o resultado do `DEL` dentro do `MULTI` | **Corrigido.** Antes fazia `SMEMBERS` e depois `DEL` em comandos separados. Um token adicionado entre os dois ficava fora do índice apagado e continuava válido. Agora roda um script Lua, que é atômico. |
| Memcached | Só retorna `true` se a geração do token ainda for a atual. Uma entrada cuja geração já caiu não conta como removida. | Inalterado (remove a geração) |

No Memcached, `findBySelector()` chamava `delete()` para limpar entradas de geração antiga. Como
`delete()` agora consulta `findBySelector()`, essa chamada passou a usar o `remove()` interno para
não entrar em recursão.

**Quebra de compatibilidade:** quem tiver um driver próprio precisa mudar a assinatura de `delete()`
para retornar `bool`. A classe ainda não foi lançada (está em *Unreleased*), então o impacto é
pequeno.

### `Authentication::rememberSession()`

Agora usa `rotate()`. Depois da rotação, carrega o usuário. Se ele não existir mais, revoga o token
novo e apaga o cookie. A gravação do cookie virou o método protegido `saveRememberCookie()`, usado
também por `rememberUser()`.

Consequência de UX: se duas abas sem sessão restaurarem o mesmo cookie ao mesmo tempo, uma delas
perde a corrida e fica deslogada naquela requisição. Antes isso já acontecia na maioria das
intercalações (a segunda recebia "token não encontrado"). Agora acontece sempre, e o comportamento
fica previsível.

## 2. Construtor com driver anulável gerava `TypeError` (média)

### Diagnóstico: procede

`__construct(?AuthDriverInterface $driver = null, ...)` repassa `$driver` para
`setDriver(AuthDriverInterface $driver)`. Por isso `new Authentication()` sempre lançava
`TypeError`. Esse defeito já existia antes do "lembrar-me" (commit `8f656f9`). A mudança apenas o
manteve.

### Solução

Assinatura alterada para `__construct(AuthDriverInterface $driver, ?RememberTokenManager $rememberTokens = null)`.
O único ponto que constrói a classe (`app/helpers.php`) já passava o driver. Nenhum chamador
precisou mudar.

## Testes

Novo auxiliar `tests/Security/Remember/InterleavedRememberTokenStorage.php`: um decorator que
executa uma ação imediatamente antes de uma operação do driver (`save`, `delete`...). Com ele, os
testes reproduzem de forma determinística a requisição concorrente.

No contrato `RememberTokenStorageTestCase`, que roda contra **Valkey 8, Memcached 1.6 e MySQL 8
reais**:

- `delete()` retorna `true` só na primeira remoção, `false` para seletor desconhecido e `false` para
  token já revogado por `deleteAllByIdentity()`;
- revogar tudo antes de gravar o token novo: a rotação falha e não sobra token válido;
- revogar tudo antes de consumir o token antigo: a rotação falha e não sobra token válido;
- revogar tudo depois da rotação: o token novo é revogado;
- duas rotações concorrentes do mesmo cookie: só uma vence.

Outros testes novos:

- `RememberTokenManagerTest`: rotação normal, cookie inválido e descarte do token novo.
- `AuthenticationRememberTest`: revogação durante a restauração da sessão deixa o usuário deslogado
  e sem tokens. Também verifica que o parâmetro `$driver` não é anulável nem opcional.

**Teste de mutação:** reintroduzi a rotação antiga (apagar e depois emitir, sem checagem). **11
testes falharam**, incluindo os de cada driver real. Depois restaurei a versão corrigida.

**Resultado:** OK (268 testes, 543 asserções) em PHP 8.2.34, 8.3.30, 8.4.18 e 8.5.11, com `phpcs`
(PSR-12) sem erros. Nenhum teste foi pulado.

Observação sobre o ambiente: o `tests/bin/run` falhou porque a porta 3306 do host estava ocupada
por outro container MySQL. O script publica `-p 3306:3306` sem necessidade, porque os testes acessam
o MySQL pela rede Docker. Rodei uma cópia do script sem esse mapeamento, e o script do repositório
não foi alterado. Vale remover a linha `-p 3306:3306` do script.

Limitação: os testes simulam a concorrência intercalando chamadas entre as operações do driver. Eles
não testam paralelismo real **dentro** de uma única operação. A atomicidade dentro de cada operação
depende das garantias do servidor: script Lua no Redis, `DELETE` único no banco e `delete` atômico
no Memcached.

## Arquivos alterados

- `springy/Security/Authentication.php`
- `springy/Security/Remember/RememberTokenManager.php`
- `springy/Security/Remember/RememberTokenStorageInterface.php`
- `springy/Security/Remember/DatabaseRememberTokenStorage.php`
- `springy/Security/Remember/RedisRememberTokenStorage.php`
- `springy/Security/Remember/MemcachedRememberTokenStorage.php`
- `tests/Security/AuthenticationRememberTest.php`
- `tests/Security/Remember/RememberTokenManagerTest.php`
- `tests/Security/Remember/RememberTokenStorageTestCase.php`
- `tests/Security/Remember/InMemoryRememberTokenStorage.php`
- `tests/Security/Remember/InterleavedRememberTokenStorage.php` (novo)
- `documentation/HISTORY.md`
