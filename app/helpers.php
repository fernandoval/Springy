<?php

/**
 * Application helper functions.
 *
 * You can put global custom functions here.
 */

use App\Security\UserSession;
use Springy\DB\Where;
use Springy\Kernel;
use Springy\Model;
use Springy\URI;

/**
 * Initiates all application dependencies.
 *
 * This method starts all application dependencies, used by some framework
 * libraries and your application. You can change its content, but try not to
 * remove our code.
 */
function bindDefaultDependencies(): void
{
    /** @var Springy\Core\Application Load the application helper. */
    $app = app();

    // Start the security hasher for user passwords.
    // We like BCrypt, but you can use another you prefer.
    $app->bind(USER_AUTH_HASHER, function () {
        return new Springy\Security\BCryptHasher();
    });

    // Define the user authentication class.
    // We made a sample authentication class User. You can change it or use another.
    $app->bind(USER_AUTH_IDENTITY, function () {
        // Here you can return a new instance of your user model class.
        return new UserSession();
    });

    // Define the authentication driver for user sign in.
    // Change the methods in your user authentication class.
    $app->bind(USER_AUTH_DRIVER, function ($drv) {
        $hasher = $drv[USER_AUTH_HASHER];
        $user = $drv[USER_AUTH_IDENTITY];

        return new Springy\Security\DBAuthDriver($hasher, $user);
    });

    // Define where the "remember me" tokens are stored. Choose the driver you prefer:
    // DatabaseRememberTokenStorage (below), RedisRememberTokenStorage (Redis or Valkey),
    // MemcachedRememberTokenStorage or your own RememberTokenStorageInterface implementation.
    // All of them are in Springy\Security\Remember namespace.
    $app->bind('security.remember.storage', function () {
        return new Springy\Security\Remember\DatabaseRememberTokenStorage(
            new Springy\Database\Connection(config_get('system.remember_me.database.connection')),
            config_get('system.remember_me.database.table')
        );
    });

    // Define the "remember me" token manager.
    $app->bind('security.remember.manager', function ($c) {
        return new Springy\Security\Remember\RememberTokenManager(
            $c['security.remember.storage'],
            config_get('system.remember_me.lifetime')
        );
    });

    // Define the authentication manager for your application.
    // Change the methods in your user authentication class.
    $app->instance(USER_AUTH_MANAGER, function ($c) {
        return new Springy\Security\Authentication($c[USER_AUTH_DRIVER], $c['security.remember.manager']);
    });

    if (PHP_SAPI !== 'cli') {
        // Initiate the flash message manager. This is used by Errors class. Do not remove it.
        $app->instance('session.flashdata', new Springy\Utils\FlashMessagesManager());

        // Initiate the input helper. You can remove it ou can use it. :)
        $app->instance('input', new Springy\Core\Input());
    }
}

/**
 * Initializes all default global template variables.
 *
 * This method set values to all template variables used by default for any system template.
 * You can change it how you want.
 *
 * THIS IS AN EXAMPLE FUNCTION.
 */
function bindDefaultTemplateVars()
{
    // Sample how to define a template function
    Kernel::registerTemplateFunction('modifier', 'has', 'inArrayPlugin');
    Kernel::registerTemplateFunction('function', 'sampleFunction', 'sampleTemplateFunction');

    // Initiates app URL template variables
    Kernel::assignTemplateVar('urlMain', URI::buildURL(['']));
    Kernel::assignTemplateVar('urlLogin', URI::buildURL(['login'], [], true, 'secure'));
    Kernel::assignTemplateVar('urlLogut', URI::buildURL(['logout'], [], true, 'secure'));

    // Sets an template variable with querystring data
    Kernel::assignTemplateVar('queryString', URI::getParams());

    // pegando a URL atual sem paramêtros para passar a tag canonical do google
    Kernel::assignTemplateVar('urlCurrentURL', URI::buildURL(URI::getAllSegments(), [], true));
}

/**
 * Modifier function to Smarty templates for in array check.
 *
 * @param mixed $array
 * @param mixed $needle
 *
 * @return bool
 */
function inArrayPlugin($array, $needle): bool
{
    return is_array($array) ? in_array($needle, $array) : false;
}

/**
 * Creates and loads a model object by given key.
 *
 * @param string $model
 * @param string $key
 * @param mixed  $value
 *
 * @return Model
 */
function loadModel(string $model, string $key, $value): Model
{
    $where = new Where();
    $where->condition($key, $value);

    return new $model($where);
}

/**
 * Sample for a simple template function.
 *
 * This is a sample of how to create a template function form Smarty.
 *
 * @param array  $params an array of parameters passed to the function.
 * @param object $smarty the Smarty object.
 *
 * @return string
 */
function sampleTemplateFunction($params, $smarty)
{
    return 'ok';
}
