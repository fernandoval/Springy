# Configurações de Banco de Dados

O arquivo `\conf\db.php` e as variantes para ambientes
`\conf\${enviroment}\db.php` armazenam as entradas de configuração para
acesso a banco de dados.

As entradas de configuração desses arquivos, são utilizadas pela classe DB,
sendo que as entradas previamente definidas não podem ser omitidas sob risco de
dano à aplicação.

Você poderá adicionar entradas personalizadas de sua aplicação.

## Entradas de configuração:

Todas as entradas de configuração de banco no índice raiz são nomes para suas
conexões de banco, sendo que três nomes são fixos do framework e não podem ser
usadas como nomes de conexão. São elas:

-   **'default'** - conexão padrão;
-   **'cache'** - configuração de cache de banco em MemcacheD;

### Entradas de configuração de conexão:

-   **'database_type'** - Determina o tipo de banco de dados. Veja "Tipos de
banco de dados suportados";
-   **'host_name'** - Nome do host.
-   **'user_name'** - Nome do usuário
-   **'password'** - Senha de acesso
-   **'database'** - Banco ou schema
-   **'charset'** - Charset do banco de dados
-   **'persistent'** - Flag de conexão persistente (true ou false)

### Tipos de banco de dados suportados:

-   **'mysql'** - Bancos de dados MySQL;
-   **'pgsql'** ou **'postgresql'** - Banco de dados PostgreSQL;
-   **'sqlite'** - Banco de dados SQLite;

### Entradas da configuração de cache de consulta em MemcacheD:

-   **'type'** - Tipo de sistema de cache ('off' | 'memcached');
-   **'server_addr'** - Endereço do servidor memcached ou caminho do arquivo de
controle;
-   **'server_port'** - Porta do servidor memcached.
