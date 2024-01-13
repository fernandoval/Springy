<?php

/*
 * Springy Framework Configuration File.
 *
 * Used for "production" environment.
 *
 * If removed, only conf/mail.php will be used.
 */

return [
    'default_driver' => 'sendgrid-api',
    'mailers' => [
        'sendgrid-api' => [
            'apikey' => 'put-the-sendgrid-api-key-here',
        ],
    ],
];
