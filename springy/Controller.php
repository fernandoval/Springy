<?php

/**
 * Parent class for controllers.
 *
 * Extends this class to construct controllers in the applications.
 *
 * @copyright 2016-2018 Fernando Val
 * @author    Allan Marques <allan.marques@ymail.com>
 * @license   https://github.com/fernandoval/Springy/blob/master/LICENSE MIT
 *
 * @version   1.1.0
 */

namespace Springy;

use Springy\Security\AclManager;

class Controller extends AclManager
{
    /** @var bool Define if the controller is restricted to signed in users. */
    protected $authNeeded = false;
    /**
     * @var bool|string|array Define a URL to redirect the user if it is not signed ($authNeeded must be true).
     *                        Can be a string or an array used by URI::buildUrl();
     **/
    protected $redirectUnsigned = false;

    /** @var Template|null The template object */
    protected $template = null;
    /** @var bool Define if the template's page must be cached. */
    protected $tplIsCached = false;
    /** @var int Define the live time (in seconds) of the cache. */
    protected $tplCacheTime = 1800; // 30 minutes default
    /// Define an identificator to the template cache.
    protected $tplCacheId = null;

    /**
     * The constructor method.
     *
     * This method is called by PHP when the object is created.\n
     * All default verification is made by this method, before other methods been called by the framework.
     */
    public function __construct()
    {
        if (app('user.auth.manager')->check()) {
            parent::__construct(app('user.auth.manager')->user());
        } else {
            parent::__construct(app('user.auth.identity'));
        }

        // Do nothing if is free for unsigned users
        if (!$this->authNeeded) {
            return;
        }

        // Verify if is an authenticated user
        if ($this->user->isLoaded()) {
            // Call user special verifications
            if (!$this->userSpecialVerifications()) {
                $this->forbidden();
            }

            // Check if the controller and respective method is permitted to the user
            $this->authorizationCheck();

            return;
        }

        // Kill the application with the 403 forbidden page.
        $this->forbidden();
    }

    /**
     * Checks the user permission for the called method.
     *
     * This is an internal method you can use to check the user permission.
     *
     * @return void
     */
    protected function authorizationCheck(): void
    {
        // Check if the controller and respective method is permitted to the user
        if (!$this->isPermitted()) {
            $this->forbidden();
        }
    }

    /**
     * Template initialization method.
     *
     * This method can be used to start your controller's view template.
     *
     * The $template object is created, it's cache is validated and then it is returned to the controller.
     *
     * @return void
     */
    protected function createTemplate($template = null): void
    {
        $this->template = new Template($template);

        if ($this->tplIsCached) {
            $this->template->setCaching('current');
            $this->template->setCacheLifetime($this->tplCacheTime);

            if (!$this->tplCacheId) {
                $this->tplCacheId = URI::currentPage();
            }

            $this->template->setCacheId($this->tplCacheId);
        }
    }

    /**
     * Sends a "403 - Forbidden" error and kill the application.
     */
    protected function forbidden()
    {
        if ($this->redirectUnsigned) {
            $this->redirect(
                (
                    is_array($this->redirectUnsigned)
                    && isset($this->redirectUnsigned['segments'])
                    && isset($this->redirectUnsigned['query'])
                    && isset($this->redirectUnsigned['forceRewrite'])
                    && isset($this->redirectUnsigned['host'])
                ) ? URI::buildURL(
                    $this->redirectUnsigned['segments'],
                    $this->redirectUnsigned['query'],
                    $this->redirectUnsigned['forceRewrite'],
                    $this->redirectUnsigned['host']
                ) : URI::buildURL($this->redirectUnsigned)
            );
        }

        new Errors(403, 'Forbidden');
    }

    /**
     * Sends a "404 - Page not found" error and kill the application.
     */
    protected function pageNotFound(): void
    {
        new Errors(404, 'Page not found');
    }

    /**
     * Sends a URL redirect to the user browser and kill the application.
     */
    protected function redirect($url): void
    {
        URI::redirect($url);
    }

    /**
     * Does all user special verifications.
     *
     * This method can be changed in child controller to extends all verification
     * you need to do on user account to grant access to page.
     *
     * Example: if you need to checks the user account is suspended.
     *
     * @return bool true if user can access the module or false if not.
     */
    protected function userSpecialVerifications(): bool
    {
        return true;
    }
}
