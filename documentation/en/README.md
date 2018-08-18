# Springy

A micro framework for smart PHP developers.

--

## Table of content

* [About](#about)
* [Installation Guide](#installation-guide)
  * [Prerequisites](#prerequisites)
  * [First steps](#first-steps)
  * [Configuration](#configuration)
* [Models](#models)
* [Views](#views)
* [Controllers](#controllers)
* [Library](#library)

## About

The **Springy** is designed to be a framework for developing web applications in PHP [MVC](https://en.wikipedia.org/wiki/Model%E2%80%93view%E2%80%93controller) architecture, lightweight, easy to learn, feature rich, adaptable and quick execution.

## Instalation Guide

We will consider that you have already configured web server to answer the request at the address of your project and explain just how to take the first steps to create the first page.

If you do not use the [Apache HTTP Server](http://httpd.apache.org/) or your web server does not understand the *.htaccess* configuration file, like [NGINX Plus](https://www.nginx.com/solutions/web-server/), check what settings are required to reproduce the same effect.

### Prerequisites

* Must have [Composer Dependency Manager for PHP](https://getcomposer.org/) installed;
* Must have a web server and [PHP](http://www.php.net) installed and configured to your project;

### First steps

* Download and unzip the framework release package into a temporary directory;
* Copy the content of www sub-directory to the document root of your project;
* Edit the [composer.json](/composer.json) file and choice the libraries you will use:
  * Choice between [Smarty](http://www.smarty.net) and [Twig](http://twig.sensiolabs.org) for template engine used as views;
  * Choice between [PHPMailer](https://github.com/PHPMailer/PHPMailer) and [SendGrid](https://github.com/sendgrid/sendgrid-php) for mailer system.
* Run Composer to download and install all dependencies;
* Edit the configuration files;
* Create your [controllers](/documentation/en/Controllers.md), [views](/documentation/en/Views.md) and [models](/documentation/en/Models.md).

### Configuration

The script *sysconf.php* is the general system configuration. Some configuration like application name and version, environment, application tree, charset and timezone. Reed [this](/documentation/en/sysconf.md) for mor details.

All configuration stays in files inside the folder defined by `'CONFIG_PATH'` in the *sysconf.php*. Default: /conf folder.

A classe `Configuration` irá buscar por arquivos contendo o sufixo `".conf.php"`, dentro dos sub-diretórios do ambiente em que o sistema estiver sendo executado. Além disso, o arquivo de mesmo nome e sufixo `".default.conf.php"` também será carregado previamente, caso exista na raíz do diretório de configurações, independete do ambiente, como sendo entradas de configuração padrão para todos os ambientes. As entradas padrão serão sobrescritas por entradas específicas do ambiente.

Os arquivos de configuração devem definir a variável de nome `$conf` como um array contendo um conjunto chave-valor.

É possível sobrescrever as configurações para determinados hosts de sua aplicação, utilizando a variável `$over_conf`, que é um array contendo no primeiro nível de índices o nome do host para o qual se deseja sobrescrever determinada(s) entrada(s) de configuração, que por sua vez, receberá um array contendo cada entrada de configuração a ser sobrescrita.

Os arquivos pré-distribuídos com o framework são de uso interno das classes e não podem ser renomeados ou omitidos.

Seu sistema poderá possuir arquivos de configuração específicos, bastando obedecer o formato e a estrutura de nomes e diretórios.

## Models

Model is part of [MVC](https://en.wikipedia.org/wiki/Model%E2%80%93view%E2%80%93controller) architecture of the framework. They are objects representing business data, rules and logic.
[Read more](/documentation/en/Models.md)

## Views

Views are part of [MVC](https://en.wikipedia.org/wiki/Model%E2%80%93view%E2%80%93controller) architecture of the framework. They are code responsible for presenting data to end users, usually files containing HTML code and special codes parsed by a template engine.
[Read mode](/documentation/en/Views.md)

## Controllers

Controller is part of [MVC](https://en.wikipedia.org/wiki/Model%E2%80%93view%E2%80%93controller) architecture of the framework. All actions is you application is started by a controller class. They are responsible for processing requests and generating responses.
[Read more](/documentation/en/Controllers.md)

## Library

The framework library is a set of classes used by itself and what the application can use too.

You can see it's [documentation here](/documentation/en/library).
