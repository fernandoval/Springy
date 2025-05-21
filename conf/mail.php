<?php

/**
 * Springy Framework Configuration File.
 *
 * Do not remove this file.
 */

return [
    /*
     * Mailer driver class name.
     *
     * Options:
     * - Springy\Mail\Drivers\PhpMailer::class
     * - Springy\Mail\Drivers\SendGrid::class
     */
    'driver' => Springy\Mail\Drivers\PhpMailer::class,

    /*
     * Errors-To header value.
     */
    'errors_go_to' => '',

    /*
     * Fake e-mail destination.
     *
     * Tells the application to send all messages to a certain e-mail
     * address instead of the actual recipient.
     */
    'fake_to' => env('FAKE_MAIL_TO', ''),

    /*
     * Settings for mailer driver.
     */
    'settings' => [
        /*
         * The SendGrid API key.
         *
         * @see https://sendgrid.com/docs/for-developers/sending-email/api-getting-started/
         */
        'apikey' => env('SENDGRID_API_KEY'),

        /*
         * The SendGrid options.
         */
        'options' => [
            'protocol' => 'https',
            'raise_exceptions' => false,
            'turn_off_ssl_verification' => false,
        ],

        /*
         * The mail transfer protocol.
         */
        'protocol' => 'smtp',

        /*
         * The SMTP host.
         */
        'host' => 'your.smtp.host.addr',

        /*
         * The SMTP port.
         */
        'port' => 587,

        /*
         * The SMTP cryptography protocol.
         */
        'cryptography' => 'tls',

        /*
         * SMTP authentication needed.
         */
        'authenticated' => true,

        /*
         * The SMTP user.
         */
        'username' => env('SMTP_USER', 'or-put-your-smtp-user-here'),

        /*
         * The SMTP password.
         */
        'password' => env('SMTP_PASS', 'or-put-your-smtp-password-here'),

        /*
         * Debug level.
         *
         * Values accepted: 0, 1 or 2
         */
        'debug' => 0,
    ],

    // System Admin (used by framework)
    'system_adm_mail' => 'noreply@yourdomain.com',
    'system_adm_name' => 'System Admin',

    // Old mailer configuration
    // @deprecated 4.7.0
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
