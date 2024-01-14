# Configurações da classe de envio de email

O arquivo `\conf\mail.php` e as variantes para ambientes
`\conf\${enviroment}\mail.php` armazenam as entradas de configuração para
tratamento de envio de email.

As entradas de configuração dos arquivos *mail*, são utilizadas pela classe
Mail, sendo que as entradas previamente definidas não podem ser omitidas sob
risco de dano à aplicação.

Você poderá adicionar entradas personalizadas de sua aplicação.

## Entradas de configuração:

-   **'errors_go_to'** - email address to notify system errors (used by
framework);
-   **'system_adm_mail'** - email address of System Admin (used by framework);
-   **'system_adm_name'** - name of System Admin (used by framework);
-   **'default_driver'** - the mailers' index name for default driver. If not
setted, the first index of mailers will be used.
-   **'mailers'** - an array with the engine mailers (see above);

### Entrada 'mailers':

The 'mailers' configuration accept this structure: 'name' => array()

Each named item of 'mailers' must have the following structure:

-   **'driver'** - a string with the mail driver name. The supported values are
'phpmailer', 'sendgrid' or 'mimemessage'.;
-   **'protocol'** - a string with the protocol accepter by the driver. In
SendGrid driver. This parameter must be into 'options' parameter.;
    -   **'smtp'** - Send thru a SMTP connection;
    -   **'sendmail'** - Send using Sendmail daemon server;
    -   **'default'**  - Send via PHP mail (default).
-   **'host'** - the mail server host address. This parameter must be into
'options' parameter;
-   **'port'** - a integer with the server port name. This parameter must be
into 'options' parameter;
-   **'cryptography'** - the secure connection type. Supported only by PHPMailer
driver;
-   **'authenticated'** - a boolean value that define if authentication is
needed. Supported only by PHPMailer driver;
-   **'username'** - username for server authentication;
-   **'password'** - password for server authentication;
-   **'options'** - as array with options for SendGrid driver;
-   **'ssl'** - turn use of SSL on (1) or off (0). Used only by MIME Message
driver;
-   **'starttls'** - turn use of StarTLS on (1) or off (0). Used only by MIME
Message driver;
-   **'direct_delivery'** - to deliver message directly to receipt's MTA (1) or
through your relay. Used only by MIME Message driver;
-   **'exclude_address'** - Used only by MIME Message driver;
-   **'workstation'** - Used only by MIME Message driver;
-   **'realm'** - Used only by MIME Message driver;
-   **'auth_host'** - Used only by MIME Message driver;
-   **'debug'** - turns the debug mode on (1, 2 or 3) or off (0). Do not
supported by SendGrid driver;
-   **'html_debug'** - debug output in HTML format. Used only by MIME Message
driver;
-   **'sendmail_path'** - command line for Sendmail program. Used only by Swift
Mailer.

## SendGrid driver

To use SendGrid Web API driver, the SendGrid-PHP packege is required.

To install SendGrid-PHP with Composer, adding the following in "require" section
at *composer.json* file:

>   "sendgrid/sendgrid": "~4.0"

## PHPMailer driver

To use PHPMailer driver, the PHPMailer packege is required.

To install PHPMailer with Composer, adding the following in "require" section at
*composer.json* file:

>   "phpmailer/phpmailer": "~5.2"

## SwiftMailer driver

To use SwiftMailer driver, the Swift Mailer packege is required.

To install Swift Mailer with Composer, adding the following in "require" section
at *composer.json* file:

>   "swiftmailer/swiftmailer": "*"
