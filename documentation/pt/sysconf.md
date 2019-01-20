# sysconf.php - Arquivo de configuração geral da aplicação

## Definição

O arquivo *sysconf.php* contém o script de configuração geral da aplicação.

## Estrutura

Este script inicialida a variável global de nome `SYSTEM` definida por `$GLOBALS['SYSTEM']`.

A variável SYSTEM é um array com a seguinte estrutura de índices:

- **'SYSTEM_NAME'** - String contendo o nome da aplicação.
- **'SYSTEM_VERSION'** - A versão da aplicação. Pode ser uma string ou um array com três índices contendo a versão maior, menor e revisão. Exemplo: `[1, 0, 0]`
- **'PROJECT_CODE_NAME'** - Apelido para o projeto.
- **'CHARSET'** - Charset do sistema.
- **'TIMEZONE'** - Timezone do sistema.
- **'ACTIVE_ENVIRONMENT'** - String contendo o nome do ambiente ativo. Exemplo: 'development'. Se for deixada em branco, o framework irá buscar entradas de configuração para o host acessado ou utilizar a variável de ambiente definida em 'ENVIRONMENT_VARIABLE'.
- **'ENVIRONMENT_VARIABLE'** - Define o nome da variável de ambiente que será usada para definir o ambiente ativo, se 'ACTIVE_ENVIRONMENT' estiver em branco e o ambiente não puder ser definido pela URI.
- **'CONSIDER_PORT_NUMBER'** - Informa à configuração por host que a porta deve ser considerada como parte do conteúdo.
- **'ENVIRONMENT_ALIAS'** - Array contendo um conjunto `chave => valor`, onde a chave representa um apelido para o ambiente e valor define o ambiente. A chave pode ser uma expressão regular.
- **'ROOT_PATH'** - Diretório root do virtual host no servidor web.
- **'PROJECT_PATH'** - Diretório root do projeto. Normalmente o diretório pai de 'ROOT_PATH'.
- **'SPRINGY_PATH'** - Diretório da biblioteca do framework.
- **'CONFIG_PATH'** - Diretório das configurações do sistema.
- **'APP_PATH'** - Diretório da aplicação.
- **'CONTROLER_PATH'** - Diretório das classes controladoras da aplicação.
- **'CLASS_PATH'** - Diretório das classes da aplicação.
- **'VAR_PATH'** - Diretório onde a aplicação irá salvar arquivos durante sua execução.
- **'MIGRATION_PATH'** - Diretório onde ficam os subdiretórios com os scriptes de mudança estrutural de banco de dados.
- **'VENDOR_PATH'** - Diretório da biblioteca de classes de terceiros.
