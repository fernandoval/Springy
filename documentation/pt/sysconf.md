# sysconf.php - Arquivo de configuração geral da aplicação

***AVISO!*** ESTE ARQUIVO FOI DESCONTINUADO. ELE CONTINUA SENDO CARREGADO NAS
VERSÕES 4.6.x, CASO EXISTA. MAS A PARTIR DA VERSÃO 4.7 OU POSTERIOR, PASSARÁ A
SER IGNORADO.

## Definição

O arquivo *sysconf.php* contém o script de configuração geral da aplicação.

Este script inicializa as configurações da aplicação na classe `Kernel`.

## Estrutura

O script retorna um array com a seguinte estrutura de índices:

*   **'SYSTEM_NAME'** - String contendo o nome da aplicação.
*   **'SYSTEM_VERSION'** - A versão da aplicação. Pode ser uma string ou um
    array com três índices contendo a versão maior, menor e revisão. Exemplo:
    `[1, 0, 0]`
*   **'PROJECT_CODE_NAME'** - Apelido para o projeto.
*   **'PROJECT_PATH'** - Diretório root do projeto.
*   **'CONFIG_PATH'** - Diretório das configurações do sistema.
*   **'APP_PATH'** - Diretório da aplicação.
*   **'VAR_PATH'** - Diretório onde a aplicação irá salvar arquivos durante sua
    execução.
*   **'MIGRATION_PATH'** - Diretório onde ficam os subdiretórios com os scriptes
    de mudança estrutural de banco de dados.
