# Springy

A micro framework for smart PHP developers.

--

## Table of content

*   [About](#about)
*   [Installation Guide](#installation-guide)
    *   [Prerequisites](#prerequisites)
    *   [First steps](#first-steps)
    *   [Configuration](#configuration)
*   [Models](#models)
*   [Views](#views)
*   [Controllers](#controllers)
*   [Library](#library)

## About

The **Springy** is designed to be a framework for developing web applications in
PHP [MVC](https://en.wikipedia.org/wiki/Model%E2%80%93view%E2%80%93controller)
architecture, lightweight, easy to learn, feature rich, adaptable and quick
execution.

## Instalation Guide

We will consider that you have already configured web server to answer the
request at the address of your project and explain just how to take the first
steps to create the first page.

If you do not use the [Apache HTTP Server](http://httpd.apache.org/) or your web
server does not understand the *.htaccess* configuration file, like
[NGINX Plus](https://www.nginx.com/solutions/web-server/), check what settings
are required to reproduce the same effect.

### Prerequisites

*   Must have [Composer Dependency Manager for PHP](https://getcomposer.org/)
    installed;
*   Must have a web server and [PHP](http://www.php.net) installed and
    configured to your project;

### First steps

*   Download and unzip the framework release package into a temporary directory;
*   Copy the content of www sub-directory to the document root of your project;
*   Edit the [composer.json](/composer.json) file and choice the libraries you
    will use:
    *   Choice between [Smarty](http://www.smarty.net) and
        [Twig](http://twig.sensiolabs.org) for template engine used as views;
    *   Choice between [PHPMailer](https://github.com/PHPMailer/PHPMailer) and
        [SendGrid](https://github.com/sendgrid/sendgrid-php) for mailer system.
*   Run Composer to download and install all dependencies;
*   Edit the configuration files;
*   Create your [controllers](/documentation/en/Controllers.md),
    [views](/documentation/en/Views.md) and
    [models](/documentation/en/Models.md).

### Configuration

The `consts` file defines the application name, version and project code name,
as well as application directory paths.

The `.env` file defines your application's configuration entries. This file
should not be added to your repository. Create it from the existing
`.env.example` file in the project root.

All other configuration stays in files inside the folder defined by `CONFIG_DIR`
constant defined in `consts` file.

#### Configuration files

The `Configuration` library class search for files with `".php"` sufix inside
environment subdirectories. If a configuration file with same target name and
`".php"` exists inside configuration folder, it will be loaded before the
environment configuration.

All *.php must returns an array with key pair.

You can sets configuration for specific hosts by defining a file named
`{conf_segment}-{host}.php`. The key pairs array returned by this files will
overwrite all other configurations.

The Springy Framework uses some pre-defined configuration files like **db**,
**mail**, **soap**, **system**, **template** and **uri**.

Any other configuration file can be used by your application.

- **[env_by_host](/documentation/en/conf/env_by_host.md)**
- **[system](/documentation/en/conf/system.md)**

## Models

Model is part of *MVC* architecture of the framework. They are objects
representing business data, rules and logic.

[Read more](/documentation/en/Models.md)

## Views

Views are part of *MVC* architecture of the framework. They are code responsible
for presenting data to end users, usually files containing HTML code and special
codes parsed by a template engine.

[Read mode](/documentation/en/Views.md)

## Controllers

Controller is part of *MVC* architecture of the framework. All actions is you
application is started by a controller class. They are responsible for
processing requests and generating responses.

[Read more](/documentation/en/Controllers.md)

## Library

The framework library is a set of classes used by itself and what the
application can use too.

You can see it's [documentation here](/documentation/en/library).
