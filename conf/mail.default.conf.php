<?php

/**
 * Springy Framework Configuration File.
 *
 * Do not remove this file.
 */

/**
 *  \defgroup emailcfg Configurações da classe de envio de email
 *  \ingroup config.
 *
 *  As entradas de configuração dos arquivos \c mail, são utilizadas pela classe Mail, sendo que as entradas previamente definidas não podem ser omitidas
 *  caso você utilize a classe.
 *
 *  \c mailers - as array with the engine mailers (see above).\n
 *  \c default_driver - the mailers' index name for default driver. If not setted, the first index of mailers will be used.\n
 *
 *  The 'mailers' configuration accept this structure: 'name' => array()
 *
 *  Each named item of 'mailers' must have the following structure:
 *
 *  \c driver - a sting with the mail driver name. The supported values are 'phpmailer', 'sendgrid' or 'mimemessage'.\n
 *  \c protocol - a string with the protocol accepter by the driver. In SendGrid driver. This parameter must be into 'options' parameter.\n
 *  \li	\c smtp     - Send thru a SMTP connection
 *  \li	\c sendmail - Send using Sendmail daemon server
 *  \li	\c default  - Send via PHP mail (default)
 *  \c host - the mail server host address. This parameter must be into 'options' parameter.\n
 *  \c port - a integer with the server port name. This parameter must be into 'options' parameter.\n
 *  \c cryptography - the secure connection type. Supported only by PHPMailer driver.\n
 *  \c authenticated - a boolean value that define if authentication is needed. Supported only by PHPMailer driver.\n
 *  \c username - username for server authentication.\n
 *  \c password - password for server authentication.\n
 *  \c options - as array with options for SendGrid driver.\n
 *  \c ssl - turn use of SSL on (1) or off (0). Used only by MIME Message driver.\n
 *  \c starttls - turn use of StarTLS on (1) or off (0). Used only by MIME Message driver.\n
 *  \c direct_delivery - to deliver message directly to receipt's MTA (1) or through your relay. Used only by MIME Message driver.\n
 *  \c exclude_address - Used only by MIME Message driver.\n
 *  \c workstation - Used only by MIME Message driver.\n
 *  \c realm - Used only by MIME Message driver.\n
 *  \c auth_host - Used only by MIME Message driver.\n
 *  \c debug - turns the debug mode on (1, 2 or 3) or off (0). Do not supported by SendGrid driver.\n
 *  \c html_debug - debug output in HTML format. Used only by MIME Message driver.\n
 *  \c sendmail_path - command line for Sendmail program. Used only by Swift Mailer.\n
 *
 *  To use SendGrid Web API driver, the SendGrid-PHP packege is required.
 *  To install SendGrud-PHP with Composer, adding the following in "require" section at composer.json file:
 *
 *  "sendgrid/sendgrid": "~4.0"
 *
 *  To use PHPMailer driver, the PHPMailer packege is required.
 *  To install PHPMailer with Composer, adding the following in "require" section at composer.json file:
 *
 *  "phpmailer/phpmailer": "~5.2"
 *
 *  To use SwiftMailer driver, the Swift Mailer packege is required.
 *  To install Swift Mailer with Composer, adding the following in "require" section at composer.json file:
 *
 *  "swiftmailer/swiftmailer": "*"
 *
 *  \see config
 */

/**
 *  \defgroup emailcfg_default Configurações da classe de envio de email para todos os ambientes
 *  \ingroup emailcfg.
 *
 *  As entradas colocadas nesse arquivo serão aplicadas a todos os ambientes do sistema.
 *
 *  Veja \link emailcfg Configurações da classe de envio de email \endlink para entender as entradas de configuração possíveis.
 *
 *  \see emailcfg
 */

$conf = [
    // Mail to notify system errors (used by framework)
    'errors_go_to'    => '',

    // System Admin (used by framework)
    'system_adm_mail' => 'noreply@yourdomain.com',
    'system_adm_name' => 'System Admin',

    // The mailer system
    'default_driver' => 'phpmailer-class',
    'mailers'        => [
        // Sample for PHP Mailer Class
        'phpmailer-class' => [
            'driver'        => 'phpmailer',
            'protocol'      => 'smtp',
            'host'          => 'smtp.gmail.com',
            'port'          => 587,
            'cryptography'  => 'tls',
            'authenticated' => true,
            'username'      => 'you@gmail.com',
            'password'      => 'put-your-password-here',
            'debug'         => 0,
        ],
        // Sample for Sendgrid API using api key
        'sendgrid-api' => [
            'driver'        => 'sendgrid',
            'apikey'        => 'put-the-sendgrid-api-key-here',
            'options'       => [
                'protocol'                  => 'https',
                // 'endpoint'                  => '/api/mail.send.json',
                // 'port'                      => null,
                // 'url'                       => null,
                'raise_exceptions'          => false,
                'turn_off_ssl_verification' => false,
            ],
        ],
        // Sample for Sendgrid API using auth method
        'sendgrid-auth' => [
            'driver'        => 'sendgrid',
            'username'      => 'put-your-sendgrid-user-here',
            'password'      => 'put-your-sendgrid-pass-here',
            'options'       => [
                'protocol'                  => 'https',
                // 'host'                      => 'smtp.sendgrid.net',
                // 'port'                      => 465,
                'raise_exceptions'          => false,
                'turn_off_ssl_verification' => false,
            ],
        ],
        // Sample using Manuel Lemos' Mime Message class - Do not use
        'mimemessage' => [
            'driver'          => 'mimemessage',
            'protocol'        => 'default',
            'host'            => 'localhost',
            'port'            => 25,
            'ssl'             => '0',
            'starttls'        => '0',
            'direct_delivery' => '0',
            'exclude_address' => '',
            'username'        => '',
            'password'        => '',
            'workstation'     => '',
            'realm'           => '',
            'auth_host'       => null,
            'debug'           => 0,
            'html_debug'      => 1,
        ],
    ],
];
