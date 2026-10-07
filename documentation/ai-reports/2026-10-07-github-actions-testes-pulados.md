# Testes pulados no GitHub Actions

- **Data:** 2026-10-07
- **Branch:** `development`
- **Arquivo alterado:** `.github/workflows/tests.yml`

## Problema

A suíte de testes passa por completo quando roda localmente com `tests/bin/run`.
No GitHub Actions ela também passa, mas **pula 26 testes**:

```
OK, but some tests were skipped!
Tests: 245, Assertions: 441, Skipped: 26.
```

## Causa

Os 26 testes pulados ficam em `tests/Security/Remember/`:

| Classe de teste                        | Testes |
|----------------------------------------|-------:|
| `RedisRememberTokenStorageTest`        | 13     |
| `MemcachedRememberTokenStorageTest`    | 13     |

Cada classe soma os 9 testes herdados de `RememberTokenStorageTestCase` aos 4
testes próprios.

As duas classes chamam `markTestSkipped()` em `createStorage()` quando falta
alguma das condições abaixo:

1. a extensão PHP correspondente (`redis` ou `memcached`) está carregada;
2. a variável de ambiente `REDIS_HOST` ou `MEMCACHED_HOST` está definida.

O ambiente local atende às duas condições. `tests/bin/Dockerfile` instala as
extensões via PECL, e `tests/bin/run` sobe os containers `valkey/valkey:8-alpine`
e `memcached:1.6-alpine`, repassando `REDIS_HOST` e `MEMCACHED_HOST` ao container
de testes.

O workflow do GitHub Actions não atendia a nenhuma das duas condições: ele não
instalava as extensões, não subia os serviços e não definia as variáveis.

## Alterações em `.github/workflows/tests.yml`

1. **Extensões PHP.** Incluí `memcached` e `redis` na lista de extensões
   instaladas pelo `shivammathur/setup-php`.
2. **Remoção do `mcrypt`.** Tirei `mcrypt` da lista. A extensão foi removida do
   núcleo do PHP na versão 7.2 e não existe para PHP 8.x, que é o que a matriz
   de versões testa.
3. **Novos serviços.** Adicionei dois serviços ao job, usando as mesmas imagens
   do script local:
   - `valkey` (`valkey/valkey:8-alpine`, porta `6379`), com health check
     `valkey-cli ping`;
   - `memcached` (`memcached:1.6-alpine`, porta `11211`).
4. **Variáveis de ambiente.** O passo "Run test suite" passou a definir
   `REDIS_HOST=127.0.0.1`, `REDIS_PORT=6379`, `MEMCACHED_HOST=127.0.0.1` e
   `MEMCACHED_PORT=11211`.
5. **Chave de cache.** Troquei a chave de cache das extensões de `cache-v1` para
   `cache-v2`, para que o job não reaproveite um cache gerado com a lista antiga
   de extensões.

Nenhum código de teste nem código da biblioteca foi alterado.

## Verificação

- O YAML do workflow foi validado sintaticamente.
- **Ainda não foi feita nenhuma execução no GitHub Actions.** Com estas
  alterações, o próximo push em `development` deve terminar com 245 testes e
  nenhum pulado.

## Pontos de atenção

- Se a instalação de `redis` ou `memcached` falhar em alguma versão da matriz,
  como a 8.5, as duas classes de teste voltam a ser puladas e o job não falha.
  Se a contagem de testes pulados não chegar a zero, confira o log do passo
  "Install PHP with extensions".
- O serviço `memcached` não tem health check. O Memcached costuma iniciar em
  instantes, então isso não deve causar problema na prática.
