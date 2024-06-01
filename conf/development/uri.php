<?php

/*
 * Springy Framework Configuration File.
 *
 * Used for "development" environment.
 *
 * If removed, only conf/uri.php will be used.
 */

return [
    'dynamic' => $_SERVER['HTTP_HOST'],
    'static' => $_SERVER['HTTP_HOST'],
    'secure' => $_SERVER['HTTP_HOST'],
];
