<?php
/** \file
 *  Springy.
 *
 *  \brief      Class Controller.
 *  \copyright  ₢ 2018 Fernando Val
 *  \author     Fernando Val - fernando.val@gmail.com
 *  \note       This class can be used to construct application controllers.
 *  \version    0.4.0.5
 *  \ingroup    framework
 */

namespace Springy;

use Springy\Security\AclManager;

class Controller extends AclManager
{
    /// Define if the controller is restricted to signed in users.
    protected $authNeeded = false;
    /// Define a URL to redirect the user if it is not signed ($authNeeded must be true). Can be a string or an array used by URI::buildUrl();
    protected $redirectUnsigned = false;

    /// The template object
    protected $template = null;
    /// Define if the template's page must be cached.
    protected $tplIsCached = false;
    /// Define the live time (in seconds) of the cache.
    protected $tplCacheTime = 1800; // 30 minutes default
    /// Define an identificator to the template cache.
    protected $tplCacheId = null;

    /**
     *  \brief The constructor method.
     *
     *  This method is called by PHP when the object is created.\n
     *  All default verification is made by this method, before other methos been called by the framework.
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
            return true;
        }

        // Verify if is an authenticated user
        if ($this->user->isLoaded()) {
            // Call user special verifications
            if (!$this->_userSpecialVerifications()) {
                $this->_forbidden();
            }

            // Check if the controller and respective method is permitted to the user
            $this->_authorizationCheck();

            return true;
        }

        // Kill the application with the 403 forbidden page.
        $this->_forbidden();
    }

    /**
     *  \brief Check the user permission for the called method.
     *
     *  This is an internal method you can use to check the user permission.
     */
    protected function _authorizationCheck()
    {
        // Check if the controller and respective method is permitted to the user
        if (!$this->isPermitted()) {
            $this->_forbidden();
        }
    }

    /**
     *  \brief Ends with a 403 - Forbidden error.
     */
    protected function _forbidden()
    {
        if ($this->redirectUnsigned) {
            if (is_array($this->redirectUnsigned) && isset($this->redirectUnsigned['segments']) && isset($this->redirectUnsigned['query']) && isset($this->redirectUnsigned['forceRewrite']) && isset($this->redirectUnsigned['host'])) {
                $url = URI::buildURL($this->redirectUnsigned['segments'], $this->redirectUnsigned['query'], $this->redirectUnsigned['forceRewrite'], $this->redirectUnsigned['host']);
            } else {
                $url = URI::buildURL($this->redirectUnsigned);
            }

            $this->_redirect($url);
        }

        new Errors(403, 'Forbidden');
    }

    /**
     *  \brief Ends with a 404 - Page not found error.
     */
    protected function _pageNotFound()
    {
        new Errors(404, 'Page not found');
    }

    /**
     *  \brief Redirect user to another URL.
     */
    protected function _redirect($url)
    {
        URI::redirect($url);
    }

    /**
     *  \brief Template initialization method.
     *
     *  This method can be used to start your controller's view template.
     *
     *  The $template object is created, it's cache is validated and then it is returned to the controller.
     *
     *  \return Return the template object.
     */
    protected function _template($template = null)
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

        return $this->template;
    }

    /**
     *  \brief Do all user special verifications.
     *
     *  This method can be changed in child controller to extends all verification you need to do on user account to grant access to page.
     *
     *  Example: if you need to check the user account is suspended.
     *
     *  \return true if user can access the module or false if not.
     */
    protected function _userSpecialVerifications()
    {
        return true;
    }
}
