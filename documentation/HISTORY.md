# Springy framework update history

## Version 4

### 4.6.2

*   Debug JavaScript updated
*   Added command line script to run PHPUnit tests on all supported PHP versions
    using Docker containers.

### 4.6.1

*   Added functions:
    *   `Kernel::getTemplatePrefix()`
    *   `Kernel::setTemplatePrefix()`
*   Added `template.smarty_extensions` (`array`) configuration to implements
    support to custom Smarty extensions
*   Fixed automatic controller routing system
*   Fixed `Security\AclManager::getAclObjectName()` function
*   Use of MemcacheD as session driver redesigned
*   Copyright page moved out to a HTML file
*   Support to Smarty template updated to version 5
*   Deprecated `Kernel::controllerRoot()` function
*   Deprecated `Kernel::VERSION` constant
*   Removed undocumented parameter from `URI::relativePathPage()` function
*   Removed third parameter from `URI::getSegment()` function
*   Removed functions and methods:
    *   `Session::_saveDbSession()`
    *   `Session::_saveMcSession()`
    *   `URI::checkRedirect()`
*   Removed configurations:
    *   `template.template_plugins_path`
    *   `uri.prevalidate_controller`
    *   `uri.redirect_last_slash`
    *   `uri.force_slash_on_index`

### 4.6.0

*   Added support to PHP 8.3;
*   Droped support to PHP 8.0;
*   Added dependency to [PHP dotenv](https://github.com/vlucas/phpdotenv);
*   Added `consts` file in project root directory. This is a PHP file to starts
    application constants. Constants in `consts` file:
    *   `APP_PATH` constant with application files path to replaces
        'APP_PATH' configuration key;
    *   `CACHE_DIR` constant with path of the directory where cache files are
        saved;
    *   `CONFIG_DIR` constant with configuration files path to replaces
        'CONFIG_PATH' configuration key;
    *   `PROJECT_ROOT` constant with project root path to replaces
        'PROJECT_PATH' configuration key;
    *   `VAR_DIR` constant with path of the var directory where cache and
        variable files are saved to replaces 'VAR_PATH' configuration key;
    *   `WEB_ROOT` constant with web server root path to replaces 'ROOT_PATH'
        configuration key;
*   Added `network` configuration file (`conf/network.php`);
*   Added:
    *   `Springy\Session::saveToDB()` function;
    *   `Springy\Session::saveToMC()` function;
    *   `Springy\Utils\SoapClient` class;
    *   `Springy\Utils\WsseAuthHeader` class;
*   New helper functions:
    *   `app_codename()` - returns the value from `APP_CODE_NAME` constant
        defined in `consts` or the value in old configuration
        'PROJECT_CODE_NAME' at `sysconf.php`;
    *   `app_name()` - returns the value from `APP_NAME` constant defined in
        `consts` or the value in old configuration 'SYSTEM_NAME' at
        `sysconf.php`;
    *   `app_path()` - returns the application path. Use this function
        instead of `Kernel::path(Kernel::PATH_APPLICATION)`;
    *   `app_version()` - returns the value from `APP_VERSION` constant defined
        in `consts` or the value in old configuration 'SYSTEM_VERSION' at
        `sysconf.php`;
    *   `cache_dir()` - returns the path for the cache directory;
    *   `config_dir()` - returns the configuration path. Use this function
        instead of `Kernel::path(Kernel::CONFIG_PATH)`;
    *   `env()` - returns the value of an environment variable. Use this
        function instead of PHP's `getenv()` function or `$_GET` superglobal to
        get environment variables;
    *   `migration_dir()` - returns the migfration scripts directory path. Use
        this function instead of `Kernel::path(Kernel::PATH_MIGRATION)`;
    *   `project_path()` - returns the project root path. Use this function
        instead of `Kernel::path(Kernel::PATH_PROJECT)`;
    *   `var_dir()` - returns the var files path. Use this function
        instead of `Kernel::path(Kernel::VAR_PATH)`.
*   Added `env_by_host` configuration file to replace removed
    'ENVIRONMENT_ALIAS' key from `sysconf.php`.
*   Added optional second parameters to `config_get()` helper function to
    defines a default value;
*   Added PHPUnit test for `Utils\JSON` class;
*   Helpers functions script moved from web root directory to `springy/Core`
    directory;
*   Migration script `public/migration.php` moved to root directory and renamed
    to `migrate`;
*   Command line script `public/cmd.php` moved to root directory and renamed to
    `cmd`;
*   `Kernel::controllerNamespace()` function now is readonly;
*   `URI::parseURI()` function now returns void;
*   Rafactored `Core\Debug` class;
*   Deprecated `sysconf.php` configuration file on web root directory. Use
    `consts` and `.env` files in project root.
*   Deprecated controllers loading without namespace and with '.page.php'
    extension for previously routing discovery;
*   Deprecated `_global.php` pre-controller load;
*   Deprecated classes:
    *   `DeepDir`;
    *   `Soap_Client` - uses `Springy\Utils\SoapClient`;
    *   `Springy\Security\AclUserInterface`;
*   Deprecated constants:
    *   `Kernel::PATH_APPLICATION` (see `app_path()` helper function);
    *   `Kernel::PATH_CLASSES`;
    *   `Kernel::PATH_CONF` (see `config_dir()` helper function);
    *   `Kernel::PATH_CONTROLLER`;
    *   `Kernel::PATH_LIBRARY`;
    *   `Kernel::PATH_MIGRATION` (see `migration_dir()` helper function);
    *   `Kernel::PATH_PROJECT` (see `project_path()` helper function);
    *   `Kernel::PATH_VAR` (see `var_dir()` helper function);
    *   `Kernel::PATH_VENDOR`;
    *   `Kernel::PATH_WEB_ROOT`;
*   Deprecated functions:
    *   `sysconf()` - use `env()` helper function;
    *   `Kernel::charset()` - use `env('CHARSET')` helper function;
    *   `Kernel::path()` - use apropriated helper function or constant;
    *   `Kernel::projectCodeName()` - use `app_codename()` helper function;
    *   `Kernel::systemName()` - use `app_name()` helper function;
    *   `Kernel::systemConfGlobal()` - use `env()` helper function;
    *   `Kernel::systemVersion()` - use `app_version()` helper function;
    *   `Session::_saveDbSession()` - use `Session::saveToDB()`;
    *   `Session::_saveMcSession()` - use `Session::saveToMC()`;
*   Removed configuration keys from `sysconf.php` (will be ignored if exists):
    *   'ACTIVE_ENVIRONMENT' - set the value in SPRINGY_ENVIRONMENT key at the
        .env file;
    *   'CHARSET' - moved to .env file;
    *   'CONSIDER_PORT_NUMBER' - already unused since v4.0;
    *   'CONTROLER_PATH';
    *   'CLASS_PATH';
    *   'ENVIRONMENT_ALIAS' - transferred to `system.environment_by_host`
        default configuration file;
    *   'ENVIRONMENT_VARIABLE' - value fixed to "SPRINGY_ENVIRONMENT";
    *   'ROOT_PATH';
    *   'SPRINGY_PATH';
    *   'TIMEZONE' - moved to .env file;
    *   'VENDOR_PATH';
*   Deprecated configurations:
    *   `conf/soap.php` - see above:
        * `soap.timeout` moved to `network.soap.timeout`;
*   Deprecated support to suffix '.conf' and '.default.conf' on configuration
    files;
*   Removed constants:
    *   `Kernel::PATH_CONFIGURATION`;
    *   `Kernel::PATH_ROOT`;
    *   `Kernel::PATH_SYSTEM`;
    *   `Kernel::PATH_CLASS`;
*   Removed functions and methods:
    *   `springyAutoload` autoload function;
    *   `Controller::_authorizationCheck()` deprecated at version 4.5;
    *   `Controller::_forbidden()` deprecated at version 4.5;
    *   `Controller::_pageNotFound()` deprecated at version 4.5;
    *   `Controller::_redirect()` deprecated at version 4.5;
    *   `Controller::_template()` deprecated at version 4.5;
    *   `Controller::_userSpecialVerifications()` deprecated at version 4.5;
    *   `Utils\JSON::getDados()` deprecated at version 4.4;
    *   `Utils\JSON::printJ()` deprecated at version 4.4;
    *   `with()` helper function;
*   Removed classes:
    *   `CreditCardValidation`;
    *   `Log`;
    *   `Utils\Excel`;
    *   `Utils\ZipFile`;
*   Removed configuratios:
    *   `uri.host_controller_path`;
    *   `uri.redirects`;
    *   `uri.routes`;
*   PHPUnit class upgraded to v10.5;
*   Droped code quality checking with [StyleCI](https://styleci.io/),
    [Travis](https://www.travis-ci.com/) and
    [Codacy](https://www.codacy.com/) (thank you for everything).

### 4.5.0

*   Added support to PHP 8.0, 8.1 and 8.2;
*   Droped support to PHP 7.x;
*   Implemented support to execute controllers using PSR-4 autoload;
*   Enhancements in kernel, configuraton and template classes;
*   `sysconf.php` returns an array instead set variable `$GLOBALS['SYSTEM']`;
*   Configuration files can returns an array instead set the variable `$conf`;
*   Changes ACL module definition;
*   Deprecates ACL default module setup;
*   Adjustments in PDO initializing;
*   Adjustments and optimizations;
*   Fixed bugs to `Migrator` an `Errors` class;
*   Added `Kernel::systemConfGlobal()` to get global system configuration data;
*   `Kernel` functions updated to readonly mode:
    *   `Kernel::charset()`;
    *   `Kernel::environment()`;
    *   `Kernel::path()`;
    *   `Kernel::projectCodeName()`;
    *   `Kernel::systemName()`;
    *   `Kernel::systemVersion()`;
*   Support to `PHPMailer` upgraded to v6.x;
*   Added GitHub Actions test case;
*   Deprecated indexes from `sysconf.php` configuration file:
    *   'CONTROLER_PATH';
    *   'CLASS_PATH';
    *   'SPRINGY_PATH';
    *   'VENDOR_PATH';
*   Deprecated classes:
    *   `CreditCardValidation`;
    *   `Log`;
    *   `Utils\Excel`;
    *   `Utils\ZipFile`;
*   Deprecated constants:
    *   `Kernel::PATH_CONFIGURATION`;
    *   `Kernel::PATH_SYSTEM` constant;
    *   `Kernel::PATH_CLASS` constant;
*   Deprecated functions:
    *   `springyAutoload` autoload function;
    *   `Controller::_authorizationCheck()`;
    *   `Controller::_forbidden()`;
    *   `Controller::_pageNotFound()`;
    *   `Controller::_redirect()`;
    *   `Controller::_template()`;
    *   `Controller::_userSpecialVerifications()`;
    *   `Container\DIContainer::has()`;
    *   `Container\DIContainer::resolve()`;
    *   `DB::castDateBrToDb()`;
    *   `DB::castDateDbToBr()`;
    *   `DB::longBrazilianDate()`;
    *   `Error::handler()`;
    *   `Events\Mediator::on()`;
    *   `Events\Mediator::off()`;
    *   `Security\AclManager::setDefaultModule()`;
    *   `Security\AclManager::getDefaultModule()`;
    *   `Strings::cep()`;
    *   `Strings::data()`;
    *   `Strings::guid()`;
    *   `Strings::hora()`;
    *   `Strings::numero()`;
    *   `Strings::sizeMatch()`;
    *   `Strings::telefone()`;
*   Removed `SOAP` class;
*   Removed functions (already deprecated):
    *   `URI::_GET()`;
    *   `URI::getHost()`;
    *   `Cookie::del()`;
*   Updated error HTML asset files;
*   Removed required suffix '.conf' and '.default.conf' from configuration
    files;
*   Deprecated `$over_conf` variable in configuration files;
*   Deprecated configuratios:
    *   `uri.host_controller_path`;
    *   `uri.prevalidate_controller`;
    *   `uri.redirects`;
    *   `uri.register_method_set_common_urls`;
    *   `uri.routes`.

### 4.4.0

*   Added support to `Controller` suffix without underscore to controller names
    to help in convertion to new namespace method;
*   Added support to `App\Controller` namespace without `Controller` or
    `_Controller` name suffix to controllers;
*   Deprecated `.class` suffix for application classes;
*   Deprecated `.static` suffix for application classes;
*   Deprecated application classes with hiphen in its name;
*   Deprecated `with()` helper function;
*   Deprecated `URI::_GET()` function;
*   Adds functions without underscore prefix to deprecate old standard
    functions;
*   Adds support to bootstrap application script as an alternative to deprecated
    `_global` controller;
*   Adds configuration to enable or disable internal special commands;
*   Enhancements in `URI` class;
*   Adds supoort to StudlyCaps controller classa names without underscores;
*   Implemented named method support using StudlyCaps pattern before with
    underscore replacing minus sign;
*   Implemented support to `__invoke()` method before `_default()` in
    controllers;
*   Fixed bug in `Configuration::set()` function;
*   Hook controllers signed as deprecated;
*   Empty function `_ignore_global` to ignore Global pre-controller deprecated;
*   Removed the following classes:
    *   `DBDelete`;
    *   `DBExpression`;
    *   `DBFiltro`;
    *   `DBInsert`;
    *   `DBSelect`;
    *   `DBUpdate`;
    *   `DBWhere`;
*   Removed `Pagination` class;
*   Deleted Doxygen documentation.

### 4.3.1

*   Adds Model column getter methods;
*   Adds helper functions;
*   Adds composer.lock file;
*   Implements secure cookie for session;
*   Implements column getter methods for Model;
*   Adjustments in `Cookie`;
*   Adjustments in `Model`;
*   Enhances in `URI`;
*   Enhances in `Validator`;
*   Fixes in `Model`;
*   Fixes in `Session`;
*   Fixes file not found `Kernel` panic;
*   Removed "SUPERVAR" query strig.

### 4.3.0

*   Implements CGI mode;
*   Breaks compatibility with PHP 7.2 or older;
*   Small adjustments and enhancements.

### 4.2.2

*   Deprecated configurations:
    *   `template.urlJS`,
    *   `template.urlCSS`;
    *   `template.urlIMG`;
    *   `template.urlSWF`;
    *   `uri.css_dir`;
    *   `uri.js_dir`;
    *   `uri.images_dir`;
    *   `uri.swf_dir`.

### 4.2.1

*   Fixed SendGrid SDK version;
*   Other enhancements and adjustments.

### 4.2.0

*   Fixed bug in `Kernel::delIgnoredError`;
*   PSR fixes in URI class;
*   Method `URI::http_host()` renamed to camel case format `URI::httpHost()`.

### 4.1.0

*   New method templateObject in Template;
*   Implemented retries in connection fails for DB;
*   Implemented when conditional for embedded objects;
*   SendGrid driver updated to support SendGrid SDK v6;
*   Components script rebuilt;
*   DB class updated to PHP 7.2 compatibility;
*   Cookie class updated to PHP 7.2 compatibility;
*   Removed following configuration entries:
    *   system.cache,
    *   system.css_path,
    *   system.js_path,
    *   system.controller_path;
*   Removed support to PHP 5.6 or older;
*   DB methods deprecated:
    *   disableReportError,
    *   enableReportError,
    *   transactionAllRollBack,
    *   num_rows,
    *   get_all,
    *   disableReportError,
    *   enableReportError,
    *   dateToTime,
    *   dateToStr;
*   Bug fix in Srints::cnpj method;
*   Bug fix in _system_bug_ magic endpoint;
*   Bug fix in Debug;
*   Deprecated classes:
    *   DBDelete,
    *   DBExpression,
    *   DBFiltro,
    *   DBInsert,
    *   DBSelect,
    *   DBUpdate,
    *   DBWhere.

### 4.0.0

*   cmd.php changed to be a *nix command line executable script (must run "chmod
    ug+x" to works);
*   Added a template property into Controller class;
*   Added option to embed in load method of Model;
*   Adjustment of Validador class to use multibyte string funcion;
*   Bug fix in _Main.php to prevent execution trying of a non public function in
    controllers;
*   Bug fix in Debug class;
*   Bug fix in Error class to prevent memory overflow;
*   Bug fix in cpf validation funcion of the Strings class;
*   Bug fix in UUID class;
*   Enhancements in Model;
*   Function types into sample model User corrected;
*   Configuration directory moved to project root;
*   Created the var directory where system writes data during the course of its
    operation;
*   Template cache directory moved to var/cache;
*   Compiled template directory moved to var/compiled;
*   Library directory moved to project root and renamed from library to springy;
*   Application directory moved to project root and renamed from system to app;
*   Vendor directory moved to project root;
*   Kernel now has the responsability by find controller and start the
    application;
*   Moved autoload and error handlers initiations to helper script;
*   Migrator class moved to library directory;
*   Migration scripts directory moved to project root;
*   Starter script simplified;
*   Removed support to HHVM.

## Version 3

### 3.6.3

*   Added a method to remove a column from the conditions in DB\Condition class;
*   Added support to configuration files in JSON format;
*   Added support to save configuration files in JSON;
*   Implemented a better control of the component files;
*   Adjustment in the template of the debug;
*   Adjustment into Controller lib class;
*   Adjustment into Errors lib class;
*   Bug fix in method to get remote IP when used inside a local area network;
*   Bug fix into Securyti\AclManager lib class;
*   Bug fix into Utils\Strings_UTF8's removeAccentedChars method;
*   Ended support to PHP 5.5.

### 3.6.2

*   Fixed bug in Model that reset the row pointer after a save when it has
    calculated columns.

### 3.6.1

*   Enhancement in JSON class;
*   Fixed bugs in Model class;
*   Fixed bugs in Validator class;
*   Fixed the "empty" IP when HTTP_X_FORWARDED_FOR header has "unknown" value;
*   Fixed bugs in minify helper when using Minify class by Matthias Mullie;
*   Documentation of URI class translated to English;
*   Correction of the name of the method who load the user data by given array
    of conditions.

### 3.6.0

*   New feature: default template directory;
*   New feature: minifing asset files from source directory;
*   New feature: category and transactional template support for SendGrid
    mailing driver;
*   Correction in private network IP detection in
    Utils\Strings::getRealRemoteAddr();
*   Adjustment in Model->update() method;
*   Session configurations moved to 'system' configurations files;
*   Session files configurations removed;
*   Adjustments in errors templates;
*   Bug fix in hook errors.

### 3.5.0

*   New main configuration 'PROJECT_CODE_NAME' to set the project code name;
*   New method Kernel::projectCodeName() to print the value of the project code
    name;
*   New class Core\Debug;
*   New class DB\Conditions;
*   New class DB\Where;
*   Adjustments in Errors class for Twig driver;
*   Adjustments in Twig driver;
*   Enhangements in Twig driver's assetFile() function;
*   Enhancements in debug system;
*   The Errors class now is dynamic;
*   The method Errors::disregard() was moved to Kernel::addIgnoredError();
*   The method Errors::regard() was moved to Kernel::delIgnoredError();
*   The method Errors::setHook() was moved to Kernel::setErrorHook();
*   The method Kernel::debug() was moved to Core\Debug::add();
*   The method Kernel::debugPrint() was moved to Core\Debug::printOut();
*   The method Kernel::getDebugContent() was moved to Core\Debug::get();
*   The method Kernel::makeDebugBacktrace() was moved to
    Core\Debug::backtrace();
*   The method Kernel::print_rc() was moved to Core\Debug::print_rc();
*   Removed method Errors::ajax();
*   Removed deprecated class Soap_Server;

### 3.4.0

*   Framework batizado de Springy;
*   Criação da classe Controller para construção de controllers;
*   Eliminação da classe Template_Static. Todas as chamadas aos métodos da
    classe devem ser renomeados conforme abaixo:
    *   chamadas a Template_Static::assignDefaultVar($name, $value) mudar para
        Kernel::assignTemplateVar($name, $value);
    *   chamadas a Template_Static::getDefaultPlugins() mudar para
        Kernel::getTemplateFunctions();
    *   chamadas a Template_Static::getDefaultVars() mudar para
        Kernel::getTemplateVar();
    *   chamadas a Template_Static::getDefaultVar($name) mudar para
        Kernel::getTemplateVar($name);
    *   chamadas a Template_Static::registerDefaultPlugin($type, $name,
        $callback, $cacheable = null, $cache_attrs = null) mudar para
        Kernel::registerTemplateFunction($type, $name, $callback, $cacheable =
        null, $cacheAttrs = null);
*   Diretório para instalação de classes de terceiros renomeado de "other" para
    "vendor", mas mantido dentro do diretório do sistema;
*   Implementado recurso de controle de versção do banco de dados no próprio
    banco. Migration cria e controla sua própria tabela para controle. Para
    rollback funcionar, agora os arquivos de script de rollback precisam ter
    mesmo nome do arquivos correspondente de migração;
*   Adicionado método Errors::setHook($error, $hook) para permitir hook em caso
    de erro.
    *   $error pode ser o código do erro ou 'default' para todos os erros;
    *   $hook se refer ao nome de uma função (string) ou método de uma classe.
        Para o segundo caso, seu valor deve ser um array no formato [(object)
        objeto, (string) método].

### 3.3.1

*   Adicionado suporte a Swift Mailer;
*   Encerramento de funções depreciadas;
*   Remoção das classes Browser;
*   Adicionadas classes de teste para parte do framework (o trabalho está apenas
    começando);
*   Ajustes de codificação para ficar em harmonia com PSR;
*   Documentação em inglês (let's go translate and write everything needed).

### 3.3.0

*   Correção de bug com sessão vazia que ocorre em usuários do Safari no MacOS;
*   Adição dos parâmetros 'order', 'offset' e 'limit' em objetos embutidos na
    Model;
*   Implementada configuração system,system_error.reported_errors(array) com
    lista de códigos de erro que devem ser reportados ao administrador do
    sistema;
*   Implementado armazenamento das variáveis de configuração do sistema na
    Kernel;
*   Implementada opção de debug de template (somente para Smarty);
*   Mudanças estruturais no script de inicialização e helper;
*   Mudança na forma como a Model trata a existência da coluna de controle de
    exclusão lógina em pesquisas;
*   Implementados filtros por IS e IS NOT na Model;
*   Nova classe de Mail e classes drivers para suporte a PHPMailer, SendGrid e
    MIME Message;
*   Remoção das classes RSS e FeedCreator;
*   Remoção das classes do Manuel Lemos;
*   Melhorias no script pós instalação e atualização para Composer:
    *   Implementada opção de minificação de arquivos;
    *   Implementada opção de desconsiderar nomes dos subdiratórios durante a
        cópia para agrupar todos os arquivos num único local.

### 3.2.1

*   Correção de bug no método URI::makeSlug();
*   Correção de bug do iconv() não funcionando no métido
    Strings_UTF8::removeAccentedChars();
*   Melhorias em Strings_ANSI::removeAccentedChars().

### 3.2.0

*   Tratamento da URL para eliminar caracteres especiais do espaço (' ') ao
    arroba ('@') dos extremos (trim) da variável $_SERVER['HTTP_HOST'];
*   Correção de bug na Model quando é feito inclusão de registro (INSERT) em
    tabela que chave primária (PK) seja composta ou não seja um inteiro
    autoincremental;
*   Correção de bug na Soap_Client que causava erro na aplicação quando
    comunicação com servidor falha;
*   Correção de bug na classe Configuration que sobrescrevia entradas com
    múltiplos níveis, apagando chaves existentes em default e não redeclaradas
    no ambiente;
*   Implementação de definição do ambiente de configuração através de variável
    de ambiente (default = FWGV_ENVIRONMENT), que pode ser definida na entrada
    'ENVIRONMENT_VARIABLE' em sysconf.php;
*   Melhoria na classe Model para permitir editar e salvar qualquer dos
    registros do resultado de busca;
*   Melhorias no processo de objetos embutidos (embeddedObj) da Model;
*   Diversas melhorias no script cmd.php para execução da aplicação em modo CLI:
    O cmd.php passa imprimir uma ajuda de sintaxe se nenhum parâmetro for
    passado.
*   Eliminação do conceito CMS (remoção da Classe, configurações e arquivos
    relacionados);
*   Implementação de filtro em classe Model embutida;
*   Implementação de objeto embutido multi-nível;
*   Implementação do sistema de controle de versão de banco de dados
    (migration).

### 3.1.3

*   Correção de bug na classe Errors em modo CLI;
*   Correção de bug na classe Mail quando modo de envio está indefinido.

### 3.1.2

*   Inclusão do conteúdo da variável mágica do PHP $_SERVER nas
    mensages/templates de erro.

### 3.1.1

*   Correção de bug na classe Soap_Client.

### 3.1.0

*   Melhorias no script post-install.php;
*   Adicionados métodos de gatilho triggerBeforeDelete, triggerBeforeInsert,
    triggerBeforeUpdate, triggerAfterDelete, triggerAfterInsert e
    triggerAfterUpdate na classe Model para permitir gatilhos para tratamentos
    antes/depois de ações de banco
*   Correção de bug na Model quando há objetos embutidos e a consulta retorna
    zero linhas;
*   Correção de bug no método templateExists da Template;
*   Correção de bug na classe Errors que ocorre quando o template de erro
    relacionado não existe;
*   Melhorias no tratamento de HTTP_HOST para configuração de ambiente pelo
    host;
*   Criação da entrada de configuração geral CONSIDER_PORT_NUMBER;
*   Implementação de integração com SendGrid Web API na classe Mail para novo
    método de envio 'sendgrid';
*   Implementação de colunas calculadas na Model;
*   Implementação de método update para alteração em massa na Model
    (experimental);
*   Adicionados os métodos disregar($error) e regard($error) na classe Errors.
    Esses métodos tem por objetivo, respectivamente, adicionar e remover códigos
    de erros da lista de ignorados pela classe. São úteis para casos de
    utilização de funções que causem a ocorrência de um erro ou alerta em caso
    de falha, mas que não interfira no funcionamento da aplicação;
*   Adicionada classe UUID para geração de códigos V3, V4, V5 e aleatório com
    base no microtime;
*   Implementada proteção contra ID de sessão vazia ou contendo caracteres
    inválidos.

### 3.0.5

*   Correção de bug no método de auto descoberta do template, que causava
    inconsistência no caminho de templates quando a classe era criada com e sem
    parâmetros;
*   Adição do método having na classe Model. Este método permite a utilização da
    cláusula SQL HAVING;
*   Correção de bug no método de montagem de back_trace do Kernel;
*   Alteração dos templates de exemplo para páginas index e de erros 404, 500 e
    503 para utilizar Bootstrap e jQuery carregados dos respectivos CDNs;
*   Criado script de pós instalação/atualização para o Composer;
*   Arquivo composer.json alterado para fazer download do Bootstrap e jQuery;
*   Alteração na URI para permitir alteração do diretório root de controladora
    por configuração de rotas;
*   Ajustes no _Main devido ao abandono de configuração de charset padrão para
    biblioteca mbstring, no PHP 5.6;
*   Criado método clearChangedColumns na classe Model para permitir limpar a
    relação de colunas alteradas;
*   Acrescentado método Model->getPKColumns que retorna um array com as colunas
    PK;
*   Correção de bug na classe Configuration;
*   Correção de bug no método Kernel::controllerNamespace;
*   Alteração na Errors para solução de problemas em sistemas que a função
    php_uname() está desativada;
*   Alteração na URI para permitir a construção de URLs com caracteres em
    maiúsculas por causa de sistemas que o servidor web é sensitivo ao contexto;
*   Implementação do atributo embeddedObj na Model, que recebe um array
    estruturado e permite embutir dados de outras classes mediante a ligação de
    chaves estrangeiras;
*   Adição do método setEmbeddedObj na Model para permitir alteração do atributo
    embeddedObj em tempo de execução.

### 3.0.4.1

*   Correção de bug na classe de erro quando o destinatário das mensagens de
    erro de sistema é um array e não uma string;
*   Criação da entrada de configuração de email 'system_adm_mail' para o
    remetente de mensagens de erro do sistema.

### 3.0.4

*   Criação do método setColumns na classe Model para permitir alteração das
    colunas a serem listadas pelo método query;
*   Criação do método groupBy na classe Model para permitir agrupamentos em
    consultas pelo método query;
*   Inclusão do caracter sharp (#) na relação de aceitos para montagem de URL em
    URI::buildURL();
*   Adição de entradas de configuração para armazenamento de logs de erro do
    sistema em banco de dados;
*   Adição da possibilidade de criação da tabela de log de erros do sistema caso
    não exista;
*   Inclusão de template de mensagem de erros do sistema (error_template.html);
*   Inclusão de template para email com mensagem de erros do sistema
    (error_email_template.html);
*   Melhorias na tela de listagem do log de erros armazenado em banco de dados.

### 3.0.3

*   Criação da entrada de configurações template.errors para definir nome dos
    templates das páginas de erros (404, 500, 503, etc.);
*   Criação da URI mágica para teste das páginas de erro. Para usá-la, chame a
    página /_error_/{codigo do erro};
*   Remoção das variáveis de template urlJS, urlCSS, urlIMG e urlSWF do método
    Errors::printHtml().

### 3.0.2

*   Criação da entrada de configuração do sistema para diretório assets;
*   Criação da entrada de configuração em templates strict_variables que quando
    verdadeiro faz classe de templates ignorar variáveis inválidas e/ou
    indefinidas;
*   Criação da entrada de configuração em uri 'assets_dir' para definição do
    diretório de assets;
*   Templates utilizando engine Smarty passam a ter disponível o método
    assetFile que versiona arquivos estáticos para evitar desatualização pelo
    cache do navegador do usuário;
*   Alteração da classe DB para reportar erros por padrão;
*   Alteração da classe Errors para não tratar erros de template (html);
*   Alteração da posição do bloco de debug dentro do HTML;
*   Outros ajustes.

### 3.0.1

*   Abandonado suporte ao NuSOAP (http://sourceforge.net/projects/nusoap/);
*   Classe Template transformada em um container da classe de template;
*   Inclusão de suporte à classe de templates Twig
    (http://twig.sensiolabs.org/);
*   Adicionado suporte ao Composer;
*   Classe Smarty removida da distribuição do framework;
*   Inclusão da classe File\File - para manipulação de arquivos do sistema de
    arquivos;
*   Inclusão da classe File\UploadedFile - para manipulação de arquivos que
    foram criados por upload no sistema de arquivos;
*   Exclusão da classe Consultar;
*   Melhorias e correções de bug na classe Model;
*   Melhoria nos métodos buildURL e makeSlug da classe URI para permitir URLs
    com '.', ',' e outros caracteres;
*   Deminificação do HTML e JavaScript embutido para impressão de debug da
    classe Kernel;
*   Correções do JavaScript para bind de Ajax do debug.

### 3.0.0

*   Inclusão da classe Container\DIContainer - Classe de container para inversão
    de controle (Dependecy Injection);
*   Inclusão da classe Core\Application - Classe container de dependências de
    toda aplicação;
*   Inclusão da classe Core\Input - Classe para gerenciamento de dados de input
    de usuário (GET e POST);
*   Inclusão da classe Events\HandlerInterface - Interface para classes handlers
    de eventos;
*   Inclusão da classe Events\Mediator - Classe de intermediadora de
    administração de eventos;
*   Inclusão da classe Security\AclManager - Classe para gerenciamento de
    permissões de identidades autenticadas na aplicação;
*   Inclusão da classe Security\AclUserInterface - Interface para padronizar as
    identidades que serão permissionadas na aplicação;
*   Inclusão da classe Security\AuthDriverInterface - Interface para padronizar
    os drivers de autenticação de identidades;
*   Inclusão da classe Security\Authentication - Gerenciador de autenticação de
    identidades;
*   Inclusão da classe Security\BasicHasher - Classe pa geração básica de
    hashes;
*   Inclusão da classe Security\BCryptHasher - Classe pa geração de hashes via
    BCrypt;
*   Inclusão da classe Security\DBAuthDriver - Driver de autenticação que
    utiliza o banco de dados como storage;
*   Inclusão da classe Security\HasherInterface - Interface para padronizar os
    geradores de hashes;
*   Inclusão da classe Security\IdentityInterface - Interface para representar
    identidades que terão uma sessão na aplicação;
*   Inclusão da classe Utils\FlashMessagesManager - Classe que gerenciar dados
    flash de sessão, ou seja, dados que ficam disponíveis por somente um
    request;
*   Inclusão da classe Utils\MessageContainer - Classe container de mensagens de
    texto;
*   Inclusão da classe Validation\Validator - Validador de dados de input do
    usuário;
*   Inclusão do Arquivo de helpers com funções e constantes para deixar o
    desenvolvedor mais feliz e produtivo;
*   Alteração na variável de configuração global 'SYSTEM' para possibilitar
    desenvolvimento de teste integrado;
*   Criado tratamento para ignorar avisos (warnings) de funções depreciadas
    (deprecated).

## Version 2

### 2.2.1

*   Script da controladora Default renomeado para _default.php;
*   Eliminação da classe FILO;
*   Implementação de sobrescrição de configuração para hosts específicos;
*   Classes ArrayUtils, JSON, JSON_Static, Rss, Strings, Strings_ANSI e
    Strings_UTF8 movidas para dentro de Utils;
*   Padronização do estilo de código conforme o PHP Framework Interop Group
    <http://www.php-fig.org/>.

### 2.2.0

*   Implementado recurso de leitura de dados de configurações utilizando sistema
    de sub-níveis separados por ponto. Ex:
    Configuration::get('db.round_robin.type') - Colaboração de Allan Marques;
*   Inclusão da classe de manipulação de arrays - ArrayUtils - Colaboração de
    Allan Marques;
*   Alteração da mensagem do handler de erro quando o sistema é executado em
    modo cli.

### 2.1.2

*   Implementado recurso de armazenamento dos templates compilados e cache dos
    templates em subdiretório para melhoria de performance em caso de grande
    quantidade de arquivos que causam lentidão no sistema operacional.

### 2.1.1

*   Correção de bug na lib FW\DB.

### 2.1.0

*   Adicionado recurso de cache de consultas de banco em memcached.

### 2.0.0

*   Criação do namespace FW.

## Version 1

### 1.4.0

*   Criação da classe Configuration;
*   Removidos métodos de configuração do sistema do Kernel;
*   Normalização do nome dos métodos das bibliotecas para o padrão
    lowerCamelCase http://pt.wikipedia.org/wiki/CamelCase (veja o documento de
    migração "migrando para versão 1.4.txt");
*   Unificação de métodos de leitura e escrita num método misto;
*   Melhorias na documentação;
*   Melhorias de consistência e correções de bugs.

### 1.3.16

*   Inclusão da biblioteca Model, para construção de modelos de acesso a banco;
*   Inclusão do framework de frontend Bootstrap;
*   Melhorias e documentação dos arquivos de configuração.

### 1.3.15

*   Ajustes para trabalhar com jQuery.

### 1.3.14

*   Atualização do Smarty.

### 1.3.13

*   Inclusão do front-end Bootstrap.

### 1.3.12

*   Diretório scripts renomeado para apenas 'js'.

### 1.3.11

*   Migração para o framework Javascript jQuery e abandono do framework
    Javascript Prototype.

### 1.3.9

*   Abandono da adoDB.

### 1.3.0 to 1.3.8

*   Alteração da classe DB para usar PDO;
*   Other history is lost forever (sorry).

### 1.2.9

*   Implementação do recurso de variáveis padrão em templates;
*   Inclusão da configuração 'PROJECT_VERSION' no sistema (sysconf.php);
*   Inclusão do método validate_uri na biblioteca URI;
*   Correção de bug no método make_debug_backtrace da biblioteca Kernel;
*   Correção de bug no método parse_uri da biblioteca URI;
*   Atualização da classe MimeMessage.

### 1.2.8

*   Implementação do recurso de redirecionamento e redirada de URL terminado em /
    para evitar duplicidade de conteúdo para SEO;
*   Inclusão do método add_attach na biblioteca Mail;
*   Melhoria no método mobile_device_detect da biblioteca Kernel;
*   Correção de bug na biblioteca Pagination;
*   Correção de bug na biblioteca Mail;
*   Outras pequenas melhorias e correções de bug.

### 1.2.7

*   Implementação do recurso de ter um host carregando as controladoras a partir
    de um subdiretório;
*   Inclusão do recurso de anexos na biblioteca Mail.php.

### 1.2.6.19

*   Corrigido bug do redirecionamento 302 do método redirect da biblioteca URI.

### 1.2.6.18

*   Adicionado controle sobre o header HTTP/1.0 Cache-Control;
*   Corrigido validação feita pelo método data da biblioteca Strings.

### 1.2.5

*   Adicionado mecanismo de acesso restrito por ambiente, configurável na
    'system'.

### 1.2.4

*   Melhorias no método "copyright" do framework.

### 1.2.3

*   Inclusão da biblioteca SOAP_Client;
*   Início do desenvolvimento da biblioteca SOAP_Server;
*   Implementação de possibilidade de sessão em banco de dados e Memcached.

### 1.2.2

*   Melhorias nos métodos de debug.

### 1.2.1c

*   Modificada a forma de armazenamento interno das variáveis default de template;
*   Adicionado método assign_default_var() à biblioteca Template para adição de
    variáveis de template;
*   Adicionado método get_default_var() à biblioteca Template para pegar valores
    de variáveis de template.

### 1.2.1b

*   Classe NuSOAP atualizada para a versão 0.9.5.

### 1.2.1

*   Adicionado uri /_pi_ que mostra as configurações do PHP - phpinfo();
*   Correções no sistema de debug;
*   Melhorias na biblioteca Pagination.

### 1.2.0c

*   Removida tag de fechamento de código PHP de todas as classes da biblioteca,
    index e configurações.

### 1.2.0b

*   Removida tag de fechamento de código PHP de algumas das classes da biblioteca.

### 1.2.0

*   Adicionada classe para criação de arquivos Excel;
*   Atualização da Smarty para a versão 3;
*   Atualização da biblioteca Template para a versão 3 da Smarty;
*   Adicionada função para vefiricar se string está codificada como UTF-8 na
    biblioteca Strings;
*   Substituída utilização da função mb_ereg_replate por preg_replace nas
    subclasses Strings_UTF8 e Strings_ANSI.

### 1.1.0

*   Adicionado método de créditos do framework;
*   Melhorias no sistema de debug;
*   Método de busca da controller movido da index.php para o URI::parse_uri;
*   Implementado conceito de configuração default;
*   Implementado sistema de rotas alternativas para controllers.

### 1.0.1.5

*   Classe MimeMessage atualizada.

### 1.0.1.4

*   Inclusão da configuração de timezone.

### 1.0.1.3

*   Inclusão das seguintes constantes de template:
    *   $HOST - string com a URL do host
    *   $CURRENT_PAGE_URI - string com a URI da página atual

### 1.0.1.2

*   Inclusão da biblioteca Javascript jQuery v1.4.2 <http://jquery.com/> no
    pacote do framework;
*   Componente TinyMCE editor atualizado para a versão 3.3.8.

### 1.0.1

*   Atualizadas as classes Smarty e ADODB;
*   Diretório system movido para dentro de www por padrão.

### 1.0.0

*   Versão inicial do framework confeccionada por Fernando Val com auxílio de
    Lucas Cardozo.