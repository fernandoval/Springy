# Revisão do Copilot: cobertura da restauração de sessão em `Authentication`

Data: 2026-10-08 — Branch: `development`

**O apontamento procede só em parte.** Três dos quatro cenários citados já tinham testes diretos
de `Authentication` em [AuthenticationRememberTest.php](../../tests/Security/AuthenticationRememberTest.php),
que existe desde o commit `f389524`. O restante da cobertura de `rememberSession()` tinha lacunas
reais, e acrescentei sete testes para fechá-las. O código de produção não mudou.

## O que já estava coberto

| Cenário citado pelo Copilot | Teste que já existia |
|---|---|
| Cookie revogado é apagado | `testThatRevokedTokensForceANewLogon` |
| Cookie malformado é apagado | `testThatLegacyCookieWithUserIdNoLongerLogsIn` (o valor `42` é rejeitado como malformado) |
| Storage fora do ar mantém o cookie | `testThatStorageOutageKeepsTheRequestAnonymousAndTheCookie` |
| Identidade removida: cookie apagado e token revogado | `testThatTokenOfRemovedUserIsDiscarded` |
| Rotação do token na restauração | `testThatValidCookieRestoresTheUserAndRotatesTheToken` (parcial, veja abaixo) |

Também já havia o teste da corrida com `revokeAllFor()`
(`testThatRevokeAllDuringRestorationIsNotBypassed`).

## Lacunas reais

1. **Rotação.** O teste conferia `check()` e o storage, mas não a sessão gravada nem o cookie novo
   enviado ao cliente. Sem chamar `saveRememberCookie()`, o usuário entraria uma vez e perderia o
   "lembrar-me" na requisição seguinte, e nenhum teste falharia.
2. **Reuso do cookie antigo** depois da rotação.
3. **Validador adulterado**, que deve revogar todos os tokens da identidade e apagar o cookie.
4. **Token expirado.**
5. **Sessão já ativa**, em que o cookie não pode ser consumido.
6. **Falha do storage no meio da rotação**, quando o token novo já foi gravado e o antigo ainda
   não foi apagado.
7. **Falha na revogação** quando a identidade não existe mais. O cookie precisa ser apagado mesmo
   assim.

## Alterações

- Novo [RecordingAuthentication.php](../../tests/Security/RecordingAuthentication.php): subclasse de
  teste que registra as chamadas de `saveRememberCookie()` e `forgetRememberCookie()`. Na CLI não
  dá para ler os cabeçalhos enviados por `setcookie()`, e `Cookie::set()` não altera `$_COOKIE`.
  Sem esse registro, não havia como verificar o valor e o prazo do cookie rotacionado.
- `startRequestWithCookie()` passou a instanciar `RecordingAuthentication`. Os testes existentes
  não mudam de comportamento.
- Novos testes em [AuthenticationRememberTest.php](../../tests/Security/AuthenticationRememberTest.php):
  - `testThatRestorationSavesTheSessionAndSendsTheRotatedCookie`: confere os dados da sessão, se o
    cookie enviado corresponde ao seletor do token novo e se o prazo é o do `RememberTokenManager`.
  - `testThatRotatedCookieCanNotBeReplayed`
  - `testThatTamperedValidatorRevokesEveryTokenOfTheIdentity`: os tokens de outro usuário são
    preservados.
  - `testThatExpiredTokenIsDiscarded`
  - `testThatActiveSessionDoesNotConsumeTheCookie`
  - `testThatStorageFailureDuringRotationKeepsTheCookieForALaterRequest`: falha no `delete()` da
    rotação. A requisição continua anônima e mantém o cookie. A requisição seguinte, com o storage
    de volta, restaura a sessão.
  - `testThatRemovedIdentityDiscardsTheCookieEvenIfRevocationFails`: o primeiro `delete()` (da
    rotação) funciona e o segundo (da revogação) falha. O cookie é apagado, e só o token órfão
    continua no storage até expirar.
- [HISTORY.md](../HISTORY.md): uma entrada sobre os novos testes.

## Verificação

- `tests/Security/AuthenticationRememberTest.php`: 22 testes e 85 asserções passando no PHP 8.2
  (container `php:8.2-cli`; o PHP local é 8.1, abaixo do mínimo do projeto).
- `tests/Security` completo: 198 testes passando. Os 73 ignorados dependem de Redis/Memcached/MySQL.
- Teste de mutação manual em `rememberSession()`. Cada alteração foi revertida depois do teste:

| Mutação | Teste que detectou |
|---|---|
| Remover a checagem `$this->user !== null` | `testThatActiveSessionDoesNotConsumeTheCookie` |
| Apagar o cookie quando o storage falha | os dois testes de falha do storage |
| Não enviar o cookie rotacionado | `testThatRestorationSavesTheSessionAndSendsTheRotatedCookie` |
| Revogar sem tratar a exceção, antes de apagar o cookie | `testThatRemovedIdentityDiscardsTheCookieEvenIfRevocationFails` |
| Trocar `rotate()` por `validate()` + `issue()` | 7 testes |

Antes dos novos testes, só a segunda e a última mutações eram detectadas.
