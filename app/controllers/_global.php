<?php
/**
 * Startup global and hook controller.
 *
 * This controller is always constructed by the framework befor the application controller.
 *
 * @copyright 2007 Fernando Val.
 * @author    Fernando Val <fernando.val@gmail.com>
 */
use Springy\Configuration;
use Springy\Kernel;
use Springy\URI;

class Global_Controller
{
    public function __construct()
    {
        date_default_timezone_set('America/Sao_Paulo');

        // Initialize all defalt dependencies, needed by framework and other controllers.
        $this->bindDefaultDependencies();

        // Como exemplo, variáveis globais de template são inicializadas para entendimento da usabilidade desse hook de controladora
        $this->bindDefaultTemplateVars();
    }

    /**
     * Initiates all application dependecies.
     *
     * This method starts all application dependencies, used by some framework libraries and you application.
     * You can change its content, but try no remove our code.
     */
    private function bindDefaultDependencies()
    {
        // Load the application helper.
        $app = app();

        // Start the security hasher for user passwords. We like BCrypt, but you can use another you prefer.
        $app->bind('security.hasher', function () {
            return $hasher = new Springy\Security\BCryptHasher();
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
     */
    private function bindDefaultTemplateVars()
    {
        // Informa para o template se o site está com SSL
        Kernel::assignTemplateVar('HTTPS', isset($_SERVER['HTTPS']));

        // Inicializa as URLs estáticas
        Kernel::assignTemplateVar('urlCSS', URI::buildURL([Configuration::get('uri', 'css_dir')], [], true, 'static'));

        // Sample how to define a template function
        Kernel::registerTemplateFunction('function', 'sampleFunction', 'sampleTemplateFunction');

        // Inicializa as URLs do site
        // Kernel::assignTemplateVar('urlMain', URI::buildURL(['']));
        // Kernel::assignTemplateVar('urlLogin', URI::buildURL(['login'], [], true, 'secure'));
        // Kernel::assignTemplateVar('urlLogut', URI::buildURL(['logout'], [], true, 'secure'));

        // conta o número de parêmetros _GET na URL
        Kernel::assignTemplateVar('numParamURL', count(URI::getParams()));
        // pegando a URL atual sem paramêtros para passar a tag canonical do google
        Kernel::assignTemplateVar('urlCurrentURL', URI::buildURL(URI::getAllSegments(), [], true));
    }
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
