# FVAL PHP Framework

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

## About

The **FVAL PHP Framework** is designed to be a framework for developing web applications in PHP [MVC](https://en.wikipedia.org/wiki/Model%E2%80%93view%E2%80%93controller) architecture, lightweight, easy to learn, feature rich, adaptable and quick execution.

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
* Create your controllers, views and models.

### Configuration

### Models

### Views

### Controllers

Controller is part of [MVC](https://en.wikipedia.org/wiki/Model%E2%80%93view%E2%80%93controller) architecture of the framework. All actions is you application is started by a controller class. They are responsible for processing requests and generating responses.
[Read more](/documentation/en/Controllers.md)