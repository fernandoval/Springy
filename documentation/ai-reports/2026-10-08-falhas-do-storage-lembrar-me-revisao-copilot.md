# Revisão do Copilot: falhas do storage do "lembrar-me" em `Authentication`

Data: 2026-10-08 — Branch: `development`

**Os três apontamentos procedem e foram corrigidos.** No item 3, escolhi renomear o método em vez
de implementar a revogação de sessões PHP. O método ainda não foi publicado em uma versão, então a
troca de nome não quebra nenhum usuário.

| # | Apontamento | Situação |
|---|---|---|
| 1 | Storage fora do ar na restauração da sessão gerava erro 500 | Procede, corrigido |
| 2 | Falha na revogação impedia a remoção do cookie no logout | Procede, corrigido |
| 3 | `logoutFromAllDevices()` prometia mais do que fazia | Procede, método renomeado para `logoutAndRevokeRememberTokens()` |

Arquivo alterado: [Authentication.php](../../springy/Security/Authentication.php).

## 1. Storage indisponível durante a restauração da sessão

**O defeito.** `rememberSession()` roda no construtor de `Authentication` e só tratava
`InvalidRememberTokenException`. Com o `LazyRememberTokenStorage`, a conexão com o backend só é
aberta nesse momento. Se o Redis/Valkey estiver fora do ar, `RememberTokenStorageException` escapa
do construtor. Qualquer requisição de um navegador com o cookie e sem sessão ativa retornava 500,
inclusive em páginas públicas, até o backend voltar ou o cookie ser apagado à mão. A mesma
exceção também podia vir de `revoke()`, no caminho do usuário removido.

Esse risco já estava registrado como pendência no relatório
[2026-10-08-lazy-remember-token-storage.md](2026-10-08-lazy-remember-token-storage.md).

**Correção.**

- `rememberSession()` captura `RememberTokenStorageException` em `rotate()` e retorna sem mexer no
  cookie. A requisição segue como anônima, e a sessão é restaurada em uma próxima requisição,
  quando o storage voltar. O cookie não é apagado porque o token continua válido. Apagá-lo
  obrigaria o usuário a fazer logon de novo por causa de uma falha temporária.
- No caminho do usuário removido, o cookie é apagado antes, e a revogação passou a usar o novo
  método `revokeRememberToken()`, que ignora falhas do storage (veja o item 2).

**Por que é seguro.** Durante a falha, ninguém é autenticado. Só deixa de acontecer a
restauração automática. `rotate()` lê e valida o token antes de gravar ou apagar qualquer coisa.
Por isso, uma falha nessa etapa não altera o estado. Se a falha ocorrer depois de gravar o novo
token, esse token fica órfão no storage e expira sozinho, porque o cliente nunca o recebeu.

## 2. Cookie removido antes da revogação no logout

**O defeito.** Em `destroyUserData()`, a ordem era: limpar a sessão, revogar o token no storage e
apagar o cookie. Uma falha no storage interrompia o método antes da última etapa. O navegador
ficava com um token válido e voltava a entrar sozinho assim que o backend se recuperasse. Além
disso, o controlador de logout recebia a exceção e não chegava a chamar `Session::destroy()`.

**Correção.**

- O cookie é apagado antes da revogação.
- A revogação passou para o novo método protegido `revokeRememberToken()`, que é *best-effort*:
  uma `RememberTokenStorageException` é ignorada. Depois que o cliente perde o cookie, o token
  que fica no storage só é útil para quem o tiver copiado, e expira no fim do prazo configurado.
  Para encerrar também esses tokens, o caminho certo é o método do item 3, que informa a falha.
- `logout()` não lança mais exceção por causa do storage, então o controlador pode chamar
  `Session::destroy()` normalmente.

**Observação.** O framework não tem um canal de log para erros que não interrompem a requisição
(`Errors::sendReport()` encerra a execução). Por isso, as falhas dos itens 1 e 2 são ignoradas
sem registro. A queda do Redis/Valkey/Memcached deve ser detectada pelo monitoramento do próprio
serviço.

## 3. `logoutFromAllDevices()` → `logoutAndRevokeRememberTokens()`

**Concordo com o apontamento.** O método revogava os tokens "lembrar-me" do usuário, mas as
sessões PHP já abertas em outros dispositivos continuavam autenticadas. `wakeupSession()` confia
nos dados da sessão e não consulta nenhum registro de revogação. O nome prometia mais do que o
método fazia.

**Por que renomear em vez de implementar.** Encerrar as sessões em todos os dispositivos exige
uma destas mudanças:

- manter um índice de IDs de sessão por usuário no handler de sessão (arquivo, Memcached, banco),
  o que mexe no componente de sessão e em todos os seus drivers; ou
- gravar uma "versão de autenticação" por usuário e conferi-la a cada requisição em
  `wakeupSession()`, o que adiciona uma consulta por requisição e exige uma coluna ou chave nova
  no modelo de identidade.

As duas mudanças são de outro escopo. Também não cabem em uma correção de revisão. O método foi
adicionado nesta mesma versão (seção *Unreleased* do `HISTORY.md`), então renomear não afeta
ninguém.

**Mudanças.**

- Novo nome: `logoutAndRevokeRememberTokens()`. O PHPDoc agora diz que sessões PHP já abertas em
  outros dispositivos não são afetadas.
- A ordem foi invertida: primeiro o logout local (sessão e cookie), depois `revokeAllFor()`. Antes,
  uma falha em `revokeAllFor()` impedia o logout local, o mesmo problema do item 2.
- Aqui a falha **não** é ignorada. `RememberTokenStorageException` continua sendo lançada
  (`@throws` documentado). Quem chama esse método quer encerrar todos os tokens, por exemplo depois
  de uma troca de senha ou de uma suspeita de roubo. Ele precisa saber que isso não aconteceu.

Uso:

```php
app('user.auth.manager')->logoutAndRevokeRememberTokens();
```

Os relatórios anteriores que citam `logoutFromAllDevices()` não foram alterados porque registram o
histórico. O `HISTORY.md` foi atualizado.

## Ponto em aberto (fora dos apontamentos)

`login($user, true)` chama `RememberTokenManager::issue()`. Se o storage estiver fora do ar, a
exceção interrompe o logon, mesmo com a sessão PHP já gravada. A tela de login retorna 500, e o
usuário não consegue entrar enquanto o backend estiver fora. Uma alternativa é concluir o logon
sem emitir o cookie, mas isso descarta sem aviso a escolha do usuário de marcar "lembrar-me".
Não alterei porque é uma decisão de produto. Recomendo decidir entre:

- (a) manter como está (falha explícita); ou
- (b) logar sem "lembrar-me" e retornar essa informação ao chamador.

## Testes

Em [AuthenticationRememberTest.php](../../tests/Security/AuthenticationRememberTest.php), o storage
indisponível é simulado com um `LazyRememberTokenStorage` cuja factory lança
`RuntimeException('Connection refused.')`, o mesmo cenário real de um Redis fora do ar.

- `testThatStorageOutageKeepsTheRequestAnonymousAndTheCookie`: a requisição segue anônima, o
  cookie continua igual e o token não é tocado.
- `testThatLogoutClearsTheCookieDuringStorageOutage`: `logout()` não lança exceção, e a sessão e o
  cookie são limpos.
- `testThatLogoutAndRevokeRememberTokensLogsOutBeforeReportingStorageOutage`: a exceção é
  lançada, mas a sessão e o cookie já foram limpos.
- O teste existente de revogação por identidade foi renomeado para o novo método.

Os três testes novos falham com o código anterior (2 erros por exceção não tratada e 1 falha de
asserção) e passam com a correção.

Resultado (`tests/bin/run`): **OK (331 tests, 695 assertions) em PHP 8.2, 8.3, 8.4 e 8.5.**
O `phpcs` (PSR-12) não aponta nada em `Authentication.php`. No teste, aparece só o aviso de
*side effect* do `require_once`, que já existia.

## Arquivos

- Alterado: `springy/Security/Authentication.php`.
- Alterado: `tests/Security/AuthenticationRememberTest.php`.
- Alterado: `documentation/HISTORY.md`.
