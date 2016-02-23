<?php
/**	\file
 *	Springy
 *
 *	\brief      Gerenciador de autenticação de identidades.
 *  \copyright  Copyright (c) 2007-2016 Fernando Val
 *  \author     Allan Marques - allan.marques@ymail.com
 *	\warning    Este arquivo é parte integrante do framework e não pode ser omitido
 *	\version    0.2.1
 *	\ingroup    framework
 */
namespace Springy\Security;

use Springy\Cookie;
use Springy\Session;

/**
 * \brief Gerenciador de autenticação de identidades.
 */
class Authentication
{
    /// Driver de autenticação
    protected $driver;
    /// Usuário autenticado na sessão
    protected $user;

    /**
     *  \brief Construtor da classe.
     *  \param [in] (\Springy\Security\AuthDriverInterface) $driver.
     */
    public function __construct(AuthDriverInterface $driver = null)
    {
        $this->setDriver($driver);

        $this->wakeupSession();
        $this->rememberSession();
    }

    /**
     *  \brief Restaurar sessão de usuário que já esteja autenticação na aplicação.
     */
    protected function wakeupSession()
    {
        $identitySessionData = Session::get($this->driver->getIdentitySessionKey());

        if (is_array($identitySessionData)) {
            $this->user = $this->driver->getDefaultIdentity();

            $this->user->fillFromSession($identitySessionData);
        }
    }

    /**
     *  \brief Restaurar sessão de usuário que sessão já esteja expirado,
     *         mas há um cookie de 'remember me'.
     */
    protected function rememberSession()
    {
        if (
            $this->user == null &&
            $id = Cookie::get($this->driver->getIdentitySessionKey())
        ) {
            $this->loginWithId($id);
        }
    }

    /**
     *  \brief Seta o driver de autenticação do gerenciador.
     *  \param [in] (\Springy\Security\AuthDriverInterface) $driver.
     */
    public function setDriver(AuthDriverInterface $driver)
    {
        $this->driver = $driver;
    }

    /**
     *  \brief Retorna o driver de autenticação do gerenciador.
     *
     *  @return (\Springy\Security\AuthDriverInterface)
     */
    public function getDriver()
    {
        return $this->driver;
    }

    /**
     *  \brief Tenta a autenticação de um usuário com as credenciais passadas por parâmetro.
     *  \param [in] (string) $login - Login do usuário
     *  \param [in] (string) $password - Senha do usuário
     *  \param [in] (bool) $remember - Indica se a sessão deve ser elmbrada por um cookie mesmo quando o tempo da sessão expirar
     *  \param [in] (bool) $loginValids - Indica se os usuários autenticados devem ser automaticamente guardados na sessão
     *  \return (boolean).
     */
    public function attempt($login, $password, $remember = false, $loginValids = true)
    {
        if ($this->driver->isValid($login, $password)) {
            if ($loginValids) {
                $this->login($this->driver->getLastValidIdentity(), $remember);
            }

            return true;
        }

        return false;
    }

    /**
     *  \brief Tenta a autenticação de um usuário com as credenciais passadas por parâmetro
     *         sem guardar seus dados na sessão caso a autenticação obtiver sucesso.
     *  \param [in] (string) $login - Login do usuário
     *  \param [in] (string) $password - Senha do usuário
     *  \return (boolean).
     */
    public function validate($login, $password)
    {
        return $this->attempt($login, $password, false, false);
    }

    /**
     *  \brief Loga o usuário na aplicação, ou seja, guarda suas informações na sessão.
     *  \param [in] (\Springy\Security\IdentityInterface) $user - Usuário para logar.
     *  \param [in] (bool) $remember - Indica se a sessão deve ser elmbrada por um cookie mesmo quando o tempo da sessão expirar.
     */
    public function login(IdentityInterface $user, $remember = false)
    {
        $this->user = $user;

        Session::set($this->driver->getIdentitySessionKey(), $this->user->getSessionData());

        if ($remember) {
            Cookie::set(
                $this->driver->getIdentitySessionKey(), //Chave do cookie
                $this->user->getId(), //Id do usuário
                5184000, //60 dias
                '/',
                config_get('session.master_domain')
            );
        }
    }

    /**
     *  \brief Loga o usuário que possui o identificador passado por parâmetro.
     *  \param [in] (variant) $id - Identificador do usuário que será logado.
     *  \param [in] (bool) $remember - Indica se a sessão deve ser elmbrada por um cookie mesmo quando o tempo da sessão expirar.
     */
    public function loginWithId($id, $remember = false)
    {
        $user = $this->driver->getIdentityById($id);

        if ($user) {
            $this->login($user, $remember);
        }
    }

    /**
     * \brief Destroi a sessão do usuário atual.
     */
    public function logout()
    {
        $this->user = null;

        $this->destroyUserData();
    }

    /**
     *  \brief Retorna se há um usuário autenticado atualmente na sessão.
     *  \return (boolean).
     */
    public function check()
    {
        return $this->user != null;
    }

    /**
     *  \brief Retorna o usuário atualmente logado na aplicação.
     *  \return (\Springy\Security\IdentityInterface).
     */
    public function user()
    {
        return $this->user;
    }

    /**
     *  \brief Destroi a sessão do usuário atualmente logado na aplicação.
     */
    protected function destroyUserData()
    {
        Session::set($this->driver->getIdentitySessionKey(), null);
        Session::unregister($this->driver->getIdentitySessionKey());

        Cookie::set(
            $this->driver->getIdentitySessionKey(),
            '',
            time() - 3600,
            '/',
            config_get('session.master_domain')
        );
        Cookie::delete($this->driver->getIdentitySessionKey());
    }
}
