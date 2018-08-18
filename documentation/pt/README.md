# Springy

A micro framework for smart PHP developers.

--

## Table of content

* [Sobre](#sobre)
* [Guia de instalação](#guia-de-instalacao)
  * [Pré-requisitos](#pre-requisitos)
  * [Primeiros passos](#primeiros-passos)
  * [Configuration](#configuration)
* [Models](#models)
* [Views](#views)
* [Controllers](#controllers)

## Sobre

O **Springy** foi projetado para ser um framework de desenvolvimentos de aplicações web em PHP no formato MVC, leve, de fácil aprendizado, rico em recursos, adaptável e de rápida execução.

## Guia de instalação

Para sermos objetivos nessa documentação, iremos considerar que você já configurou o servidor web para responder no endereço de seu projeto e focar como fazer para desenvover sua primeira página.

Se você não usa o [Apache HTTP Server](http://httpd.apache.org/) ou seu servidor web não entende o arquivo *.htaccess*, como o [NGINX Plus](https://www.nginx.com/solutions/web-server/), verifique quais configurações são necessárias para ter o mesmo efeito.

### Pré-requisitos

* Ter o [Composer Dependency Manager for PHP](https://getcomposer.org/) instalado;
* Ter um servidor web e o [PHP](http://www.php.net) instalados e configurados para seu projeto;

### Primeiros passos

* Faça o download do pacote do framework e o descompacte num diretório temporário;
* Copie o conteúdo da pasta www para o diretório *document root* de seu projeto;
* Edite o arquivo [composer.json](/composer.json) e escolha as bibliotecas que irá usar:
  * Escolha entre as classes de template [Smarty](http://www.smarty.net) e [Twig](http://twig.sensiolabs.org) para processar as views de seu projeto;
  * Escolha entre as classes [PHPMailer](https://github.com/PHPMailer/PHPMailer) e [SendGrid](https://github.com/sendgrid/sendgrid-php) para o sistema de envio de e-mails.
* Rode o Composer para fazer o download e a instalação das dependências;
* Edite os arquivos de configuração;
* Crie suas controladoras (controller), visões (view) e modelos (model).

### Configuração

O script *sysconf.php* é o arquivo de configuração geral do sistema. Nele estão entradas de definição como nome e versão do sistema, ambiente, árvore de diretórios da aplicação, charset e timezone. Mais detalhes [aqui](/documentation/pt/sysconf.md).

As demais configurações da aplicação deverão estar no diretório e sub-diretórios definidos pela entrada `'CONFIG_PATH'`.

The `Configuration` library class search for files with `".conf.php"` sufix inside environment subdirectories. If a configuration file with same target name and sufix `".default.conf.php"` exists inside configuration folder, it will be loaded before the environment configuration.

All *.conf.php must define an array variable named `$conf` with key pair.

You can overwrite key pairs configuration for specific hosts by defining the array `$over_conf` where the first key is the host and is value is an array with key pairs to be overwriten.

The Springy Framework uses some pre-defined configuration files like **db**, **mail**, **soap**, **system**, **template** and **uri**.

All other configuration files are used only by your application.

## Models

## Views

## Controllers