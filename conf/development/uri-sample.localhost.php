<?php

/*
 * Springy Framework Configuration File.
 *
 * This is a sample file that will be loaded after uri.conf in case the URL host
 * is sample.localhost
 *
 * You can define any configuration file with the hostname to overwrite any
 * configuration using the {conf}-{host}.php pattern.
 */

return [
    'host.seusite.localhost' => [
        'dynamic' => 'http://sample.localhost',
    ],
];
