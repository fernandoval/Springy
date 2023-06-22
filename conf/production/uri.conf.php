<?php

/**
 * Springy Framework Configuration File.
 *
 * Used for "production" environment.
 *
 * If removed, only uri.default.conf.php will be used.
 */
$conf = [
    'dynamic' => $_SERVER['HTTP_HOST'],
    'static' => $_SERVER['HTTP_HOST'],
    'secure' => $_SERVER['HTTP_HOST'],
];
