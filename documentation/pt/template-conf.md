# Configurações da classe de templates

O arquivo `\conf\template.default.conf.php` e as variantes para ambientes
`\conf\${enviroment}\template.conf.php` armazenam as entradas de configuração
para tratamento de templates.

As entradas de configuração dos arquivos *template*, são utilizadas pela classe
Template, sendo que as entradas previamente definidas não podem ser omitidas sob
risco de dano à aplicação.

Você poderá adicionar entradas personalizadas de sua aplicação.

## Entradas de configuração:

-   **'template_engine'** - Define a classe de renderização de templates. Os
valores aceitos são:
    -   **'smarty'** - para utilizar a classe Smarty como mecanismo de
    templates;
    -   **'twig'** - para utilizar a classe Twig como mecanismo de templates.
-   **'strict_variables'** - Buleano que quando *true* informa à classe de
templates para ignorar variáveis inválidas e/ou indefinidas;
-   **'auto_reload'** - see Twig documentation to understand;
-   **'debug'** - Buleano que quando true liga o debug de template;
-   **'autoescape'** - see Twig documentation to understand this
http://twig.sensiolabs.org/doc/api.html#environment-options
-   **'optimizations'** - see Twig documentation to understand;
-   **'debugging_ctrl'** - Variável debugging_ctrl do Smarty. Aceita os valores
'URL' ou 'NONE'. (vide documentação do Smarty);
-   **'template_path'** - Caminho de disco do diretório de templates;
-   **'template_config_path'** - Caminho de disco do diretório configuração de
templates;
-   **'compiled_template_path'** - Caminho de disco do diretório configuração de
templates compilados;
-   **'template_cached_path'** - Caminho de disco do diretório configuração de
templates cacheados;
-   **'use_sub_dirs'** - Valor booleano que informa para a classe de templates
que os compilados e cache deverão ser armazenados em subdiretórios. Esse recurso
é importante para aplicações com grande quantidade de páginas/templates ou que
utilizem cache de template que gere grande quantidade de arquivos. Diretórios
contendo milhares de arquivos criam problema de performance porque, nesses
casos, o sistema de arquivos do sistema operacional demora a responder.
