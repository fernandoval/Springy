<?php

/**
 * Bootstrap application.
 *
 * @copyright 2022 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 * @license   https://github.com/fernandoval/Springy/blob/master/LICENSE MIT
 *
 * @version   1.0.0
 */

use Springy\Kernel;
use Springy\URI;

/**
 * Initiates all application dependecies.
 *
 * This method starts all application dependencies, used by some framework libraries and you application.
 * You can change its content, but try no remove our code.
 */
function bindDefaultDependencies(): void
{
    // Load the application helper.
    $app = app();

    // Start the security hasher for user passwords. We like BCrypt, but you can use another you prefer.
    $app->bind('security.hasher', function () {
        return new Springy\Security\BCryptHasher();
    });

    // Define the user model class. We made a sample model class User. You can change it or use another.
    $app->bind('user.auth.identity', function () {
        // Here you can return a new instance of your user model class.
        return new User();
    });

    // Define the authentication driver for test users sign in. Change the methods in your user model class.
    $app->bind('user.auth.driver', function ($c) {
        $hasher = $c['security.hasher'];
        $user = $c['user.auth.identity'];

        return new Springy\Security\DBAuthDriver($hasher, $user);
    });

    // Define the authentication manager for you application. Change the methods in your user model class.
    $app->instance('user.auth.manager', function ($c) {
        return new Springy\Security\Authentication($c['user.auth.driver']);
    });

    // Initiate the flash message manager. This is used by Errors class. Do not remove it.
    $app->instance('session.flashdata', new Springy\Utils\FlashMessagesManager());

    // Initiate the input helper. You can remove it ou can use it. :)
    $app->instance('input', new Springy\Core\Input());
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
 * Sample for a simple template function.
 *
 * This is a sample of how to create a template function form Smarty.
 *
 * @param array  $params
 * @param object $smarty
 *
 * @return string
 */
function sampleTemplateFunction($params, $smarty)
{
    // $params is an assay of parameters passed to the function.
    // $smarty is the Smarty object.
    // foreach ($params as $var => $value) {
    //     $var is the variable name
    //     $value is it value
    // }

    return 'ok';
}

date_default_timezone_set('America/Sao_Paulo');

// Initialize all default dependencies, needed by framework and other controllers.
bindDefaultDependencies();

// An example function to start default template variables.
bindDefaultTemplateVars();
