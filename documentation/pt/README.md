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

## Models

## Views

## Controllers