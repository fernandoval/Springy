<?php

/*
 * Springy Framework Configuration File.
 *
 * Used for "production" environment.
 *
 * If removed, only mail.default.conf.php will be used.
 */
$conf = [
    'default_driver' => 'sendgrid-api',
    'mailers'        => [
        'sendgrid-api' => [
            'apikey' => 'put-the-sendgrid-api-key-here',
        ],
    ],
];
