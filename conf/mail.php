<?php

/**
 * Springy Framework Configuration File.
 *
 * Do not remove this file.
 */

return [
    // Mail to notify system errors (used by framework)
    'errors_go_to' => '',

    // System Admin (used by framework)
    'system_adm_mail' => 'noreply@yourdomain.com',
    'system_adm_name' => 'System Admin',

    // The mailer system
    'default_driver' => 'phpmailer-class',
    'mailers' => [
        // Sample for PHP Mailer Class
        'phpmailer-class' => [
            'driver' => 'phpmailer',
            'protocol' => 'smtp',
            'host' => 'smtp.gmail.com',
            'port' => 587,
            'cryptography' => 'tls',
            'authenticated' => true,
            'username' => 'you@gmail.com',
            'password' => 'put-your-password-here',
            'debug' => 0,
        ],
        // Sample for Sendgrid API using api key
        'sendgrid-api' => [
            'driver' => 'sendgrid',
            'apikey' => 'put-the-sendgrid-api-key-here',
            'options' => [
                'protocol' => 'https',
                // 'endpoint' => '/api/mail.send.json',
                // 'port' => null,
                // 'url' => null,
                'raise_exceptions' => false,
                'turn_off_ssl_verification' => false,
            ],
        ],
        // Sample for Sendgrid API using auth method
        'sendgrid-auth' => [
            'driver' => 'sendgrid',
            'username' => 'put-your-sendgrid-user-here',
            'password' => 'put-your-sendgrid-pass-here',
            'options' => [
                'protocol' => 'https',
                // 'host' => 'smtp.sendgrid.net',
                // 'port' => 465,
                'raise_exceptions' => false,
                'turn_off_ssl_verification' => false,
            ],
        ],
        // Sample using Manuel Lemos' Mime Message class (NOT IMPLEMENTED)
        'mimemessage' => [
            'driver' => 'mimemessage',
            'protocol' => 'default',
            'host' => 'localhost',
            'port' => 25,
            'ssl' => '0',
            'starttls' => '0',
            'direct_delivery' => '0',
            'exclude_address' => '',
            'username' => '',
            'password' => '',
            'workstation' => '',
            'realm' => '',
            'auth_host' => null,
            'debug' => 0,
            'html_debug' => 1,
        ],
    ],
];
