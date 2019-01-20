# Arquivo de Configuração do Sistema (system)

Os arquivos **system.default.conf.php** na pasta /conf e **system.conf.php** nas sub-pastas de configuração por ambiente são usadas pela **Kernel** e outras bibliotecas do framework.

## Entrada de configurações pré-definidas

- **'debug'** - Define se o modo de depuração está ativo.
- **'ignore_deprecated'** - Instrui o handler de tratamento de erros a ignorar avisos de funções depreciadas.
- **'rewrite_url'** - Define se rewrite de URL está ativo.
- **'cache-control'** - Define o header HTTP/1.1 Cache-Control
- **'authentication'** - Define o acesso autenticado por HTTP. Esse parâmetro de configuração é um array no seguinte formato: array('user' => 'usuario', 'pass' => 'senha'). Se o array estiver vazio, desliga a requisição de autenticação HTTP.
- **'developer_user'** - Nome da variável query string para ativar/desativar o [modo desenvolvedor](#modo-desenvolvedor)
- **'developer_pass'** - Valor para ativar o modo desenvolvedor/dba.
- **'dba_user'** - Nome da variável query string para ativar/desativar o [modo DBA](#modo-dba)
- **'bug_authentication'** - Habilita autenticação HTTP para acesso à página de log de erros do sistema. Esse parâmetro de configuração espera um array vazio ou no seguinte formato: ['user' => 'usuario', 'pass' => 'senha']
- **'assets_source_path'** - Caminho da pasta de fontes dos arquivos complementares do website (assets).
- **'assets_path'** - Caminho das pasta dos minificados dos arquivos complementares (acessível via web).
- **'maintenance'** - Coloca o sistema em modo de manutenção e todas as requisições serão respondidas com erro HTTP 503.
- **'session'** - Configurações de Sessão.

### Configuração de sessão

A entrada `'session'` é um array contendo outras entradas que configuram o sistema de sessão do framework.

- **'type'** - String contendo [tipo de mecanismo de armazenamente da sessão](#tipos-de-controle-de-sessao). Os valores possíveis são `'file'`, `'memcached'` e `'database'`.
- **'name'** - O nome do cookie de sessão.
- **'domain'** - O domínio principal do cookie de sessão.
- **'expires'** - O tempo de validade da sessão em minutos.

## Tipos de controle de sessão

O framework dá nativamente suporte aos seguintes tipos de controle de sessão de usuário:

- **'file'** - Armazenamento em arquivo. Esse é o formato padrão suportado pelo PHP.
- **'memcached'** - Armazenamento em serviço Memcache. Requer um servidor Memcache externo ou o serviço MemcacheD rodando no computador.
- **'database'** - Armazenamento em tabela de banco de dados relacional. Recomenda-se usar tabelas do tipo memória. Se seu SGBD não tem suporte a esse tipo de tabela, desaconselha-se seu uso.

## Modo desenvolvedor

O Springy possui entradas de configuração que permitem aos desenvolvedores do sistema ligarem o modo de depuração em ambientes onde este esteja desligado e acessar ambientes colocados em modo de manutenção.

As entradas `'developer_user'` e `'developer_pass'` são entendidas pela Kernel como um acesso especial se seus valores forem recebidos como chave-valor passados por query string, da seguinte forma: *`www.meusite.com.br/?{$developer_user}={$developer_pass}`*

Por exemplo, suponhamos que as entradas de configurção definam da seguinte forma:

```php
[
    // ... outras entradas de configuração
    'developer_user' => 'silvio',
    'developer_pass' => 'santos',
];
```

Então, se o desenvolvedor colocar query string *?silvio=santos* na URI, o modo desenvolvedor será ligado durante toda a sessão.

Para desativar o modo desenvolvedor sem precisar limpar cookies ou fechar o navegador, basta entrar a seguinte cadeia de chaves query string: *`?{$developer_user}=off`*

Portanto o valor da entrada `'developer_pass'` nunca poderá ser **'off'**, pois não será possível ativar o modo desenvolvedor.

## Modo DBA

Assim como o [modo desenvolvedor](#modo-desenvolvedor), o framework possui um mecanimos para habilitar a depuração, exibindo **todos** os comandos *SQL* executados durante a iteração.

A entrada de configuração `'dba_user'` define o nome da variável query string que ativa ou desativa a depuração de comandos *SQL*.

Para ligar este modo, primeiro deve-se habilitar o modo desenvolvedor e em seguida o modo DBA ou fazê-lo em conjunto da seguinte forma: *`www.meusite.com.br/?{$developer_user}={$developer_pass}&{$dba_user}={$developer_pass}`*

Para exemplificar, consideremos que as entradas de configuração contenham os seguintes valores:

```php
[
    // ... outras entradas de configuração
    'developer_user' => 'silvio',
    'developer_pass' => 'santos',
    'dba_user'       => 'vemaih',
];
```

Então para ativar o modo DBA o desenvolvedor deverá colocar a query string *?silvio=santos&vemaih=santos* na URI.

Para desarivar o modo DBA, a query string deverá conter o seguinte: *`?{$dba_user}=off`*
