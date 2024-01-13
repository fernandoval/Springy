<?php

/*
 * Springy Framework Configuration File.
 *
 * Used for "development" environment.
 *
 * If removed, only conf/mail.php will be used.
 */

return [
    'default_driver' => 'phpmailer-class',
    'mailers'        => [
        'phpmailer-class' => [
            'username' => 'you@gmail.com',
            'password' => 'put-your-password-here',
            'debug'    => 0,
        ],
    ],
];
