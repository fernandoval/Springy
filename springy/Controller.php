<?php

/**
 * Parent class for controllers.
 *
 * Extends this class to construct controllers in the applications.
 *
 * @copyright 2016-2018 Fernando Val
 * @author    Allan Marques <allan.marques@ymail.com>
 * @license   https://github.com/fernandoval/Springy/blob/master/LICENSE MIT
 */

namespace Springy;

use Springy\Exceptions\HttpErrorForbidden;
use Springy\Exceptions\HttpErrorNotFound;
use Springy\Security\AclManager;

class Controller extends AclManager
{
    /** Define if the controller is restricted to signed in users. */
    protected bool $authNeeded = false;
    /** Define a URL to redirect the user if it is not signed ($authNeeded must be true). */
    protected array $redirectUnsigned = [
        'enabled'      => false,
        'segments'     => [],
        'query'        => [],
        'forceRewrite' => false,
        'host'         => 'dynamic',
    ];

    /** The template object */
    protected ?Template $template = null;
    /** Define if the template's page must be cached. */
    protected bool $tplIsCached = false;
    /** Define the live time (in seconds) of the cache. */
    protected int $tplCacheTime = 1800; // 30 minutes default
    /**Define an identificator to the template cache. */
    protected ?string $tplCacheId = null;

    /**
     * The constructor method.
     *
     * This method is called by PHP when the object is created.\n
     * All default verification is made by this method, before other methods been called by the framework.
     */
    public function __construct()
    {
        parent::__construct(
            app('user.auth.manager')->check()
                ? app('user.auth.manager')->user()
                : app('user.auth.identity')
        );

        if (!$this->authNeeded) {
            // Do nothing if is free for unsigned users
            return;
        } elseif (!$this->user->isLoaded()) {
            // Has no user logged in then kill the application with the 403 forbidden page.
            $this->forbidden();

            return;
        }

        // Call user special verifications then...
        $this->userSpecialVerifications()
            // check if the controller and respective method is permitted to the user
            ? $this->authorizationCheck()
            // or kill with the 403 forbidden page
            : $this->forbidden();
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
    protected function forbidden(): never
    {
        $this->redirectUnsigned['enabled']
            ? $this->redirect(
                URI::buildURL(
                    $this->redirectUnsigned['segments'] ?? [],
                    $this->redirectUnsigned['query'] ?? [],
                    $this->redirectUnsigned['forceRewrite'] ?? false,
                    $this->redirectUnsigned['host'] ?? 'dynamic'
                )
            )
            : throw new HttpErrorForbidden();
    }

    /**
     * Sends a "404 - Page not found" error and kill the application.
     */
    protected function pageNotFound(): never
    {
        throw new HttpErrorNotFound();
    }

    /**
     * Sends a URL redirect to the user browser and kill the application.
     */
    protected function redirect($url): never
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
