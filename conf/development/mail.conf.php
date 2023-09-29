<?php

/*
 * Springy Framework Configuration File.
 *
 * Used for "development" environment.
 *
 * If removed, only mail.default.conf.php will be used.
 */
$conf = [
    'default_driver' => 'phpmailer-class',
    'mailers'        => [
        'phpmailer-class' => [
            'username' => 'you@gmail.com',
            'password' => 'put-your-password-here',
            'debug'    => 0,
        ],
    ],
];
