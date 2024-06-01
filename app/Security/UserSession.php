<?php

/**
 * Example user authentication and session manager class.
 */

namespace App\Security;

use App\Models\User;
use Springy\Exceptions\SpringyException;
use Springy\Security\IdentityInterface;
use Springy\Session;
use Springy\URI;
use Springy\Utils\MessageContainer;

class UserSession implements IdentityInterface
{
    // Authentication remember me cookie name
    private const IDENTITY_COOKIE = '_YourSessionCookieName_';

    // User's data cache time
    private const CACHE_TIME = '+5 minutes';

    // Control properties
    private const CTRL_PERMISSIONS = 'permissions';
    private const CTRL_EXPIRATION = 'expiration';

    // User's data
    private const USER_ID = 'user_id';
    private const USER_DATA = 'data';

    public const ACL_SEPARATOR = '|';

    // user's id
    private int|null $userId;

    // user's data
    private array $userData;

    // string user's password memorized for login check
    private string|null $password;

    /** @var array admin modules accessible */
    private $modules;
    // user's permissions regex list
    private array $permissions;

    // user's data cache expiration time
    private int $expirationTime;

    /** @var \Springy\Utils\MessageContainer */
    private $errors;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->clearUser();
    }

    /**
     * Gets any user data.
     *
     * @param string $name
     *
     * @return mixed
     */
    public function __get(string $name): mixed
    {
        if ($name === 'modules') {
            return $this->modules;
        } elseif ($name === User::COL_PASSWD) {
            return $this->password;
        }

        return $this->userData[$name] ?? null;
    }

    /**
     * Sets any user data.
     *
     * @param string $name
     * @param mixed  $value
     */
    public function __set(string $name, mixed $value)
    {
        $user = new User();

        if (!in_array($name, $user->getWritableColumns())) {
            throw new SpringyException('Undefined user column');
        }

        $this->userData[$name] = $value;
    }

    /**
     * Clears user data.
     *
     * @return void
     */
    private function clearUser(): void
    {
        $this->userId = null;
        $this->userData = [];
        $this->password = null;

        $this->permissions = [];
        $this->expirationTime = 0;
        $this->errors = null;
    }

    /**
     * Loads the user permissions array.
     *
     * @return void
     */
    private function loadPermissions(): void
    {
        $this->permissions = [
            'GET' => [''],
            'PUT' => [],
            'POST' => [],
            'DELETE' => [],
        ];
    }

    /**
     * Load the user data from the session.
     *
     * This method is executed to load the user data by a given array with the data columns.
     *
     * @param array
     *
     * @return void
     */
    public function fillFromSession(array $data): void
    {
        $this->userId = $data[self::USER_ID] ?? $this->userData[User::COL_ID] ?? null;
        $this->userData = $data[self::USER_DATA] ?? [];

        $this->permissions = $data[self::CTRL_PERMISSIONS] ?? [];
        $this->expirationTime = $data[self::CTRL_EXPIRATION] ?? 0;

        if ($this->expirationTime < time()) {
            $this->refreshSession();
        }
    }

    /**
     * Get the user credentials.
     *
     * @return array the array with credential data.
     */
    public function getCredentials(): array
    {
        return [
            'login' => User::COL_EMAIL,
            'password' => User::COL_PASSWD,
        ];
    }

    /**
     * Returns user's email.
     *
     * @return string|null
     */
    public function getEmail(): ?string
    {
        return $this->__get(User::COL_EMAIL);
    }

    /**
     * Returns user's email hashed in SHA-1.
     *
     * @return string|null
     */
    public function getHashedEmail(): ?string
    {
        return sha1($this->__get(User::COL_EMAIL) ?? '');
    }

    /**
     * Get the user's UUID.
     *
     * @return string|null.
     */
    public function getId()
    {
        return $this->__get(User::COL_UUID);
    }

    /**
     * Get the user uuid column name.
     *
     * @return string the column name of the user uuid.
     */
    public function getIdField(): string
    {
        return User::COL_UUID;
    }

    /**
     * Returns user's name.
     *
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->__get(User::COL_NAME);
    }

    /**
     * Returns user's primary key (id).
     *
     * @return int|null
     */
    public function getPK(): ?int
    {
        return $this->userId;
    }

    /**
     * Returns the User model for current user.
     *
     * @return User
     */
    public function getUser(): User
    {
        return loadModel(User::class, User::COL_ID, $this->getPK());
    }

    /**
     * Get the session data.
     *
     * @return array the array with data to be saved in session.
     */
    public function getSessionData(): array
    {
        return [
            self::USER_ID => $this->userId,
            self::USER_DATA => $this->userData,

            self::CTRL_PERMISSIONS => $this->permissions,
            self::CTRL_EXPIRATION => $this->expirationTime,
        ];
    }

    /**
     * Get the session key.
     *
     * @return string the name of the session key.
     */
    public function getSessionKey(): string
    {
        return self::IDENTITY_COOKIE;
    }

    /**
     * Returns last validation errors on trying to save data.
     *
     * @return MessageContainer|null
     */
    public function getValidationErrors(): ?MessageContainer
    {
        return $this->errors;
    }

    /**
     * Get the user permission for the given ACL.
     *
     * @param string $aclObjectName the name of the ACL.
     *
     * @return bool True if the user has permission to access or false if not.
     */
    public function hasPermissionFor(string $aclObjectName): bool
    {
        $permissions = $this->permissions[URI::requestMethod()] ?? [];

        return count(array_filter(
            $permissions,
            fn ($permission) => preg_match_all(
                '/^' . $permission . '/A',
                $aclObjectName,
                $matches,
                PREG_SET_ORDER,
                0
            )
        )) > 0;
    }

    /**
     * Returns true if the user's data was loaded.
     *
     * @return bool
     */
    public function isLoaded(): bool
    {
        return !is_null($this->userId);
    }

    /**
     * Loads the identity data by given credential.
     *
     * This method is executed when the user is loaded by a given array of conditions for a query.
     *
     * @param array $data the array with the condition to load the data.
     *
     * @return void
     */
    public function loadByCredentials(array $data): void
    {
        $user = loadModel(User::class, key($data), current($data));

        if (!$user->isLoaded()) {
            $this->clearUser();

            return;
        }

        $this->userId = (int) $user->get(User::COL_ID);

        $this->userData = [
            User::COL_EMAIL => $user->get(User::COL_EMAIL),
            User::COL_UUID => $user->get(User::COL_UUID),
            User::COL_NAME => $user->get(User::COL_NAME),
            User::COL_CREATED_AT => $user->get(User::COL_CREATED_AT),
        ];

        $this->password = $user->get(User::COL_PASSWD);

        $this->loadPermissions();

        $this->expirationTime = strtotime(self::CACHE_TIME);
    }

    /**
     * Refreshes the user's data and updates the session.
     *
     * @return void
     */
    public function refreshSession(): void
    {
        if (is_null($this->getPK())) {
            return;
        }

        $this->loadByCredentials([User::COL_UUID => $this->getId()]);
        $this->updateSession();
    }

    /**
     * Saves the user data.
     *
     * @return bool
     */
    public function save()
    {
        $user = $this->getUser();

        if (!$user->isLoaded()) {
            throw new SpringyException('User not found', E_USER_ERROR);
        }

        foreach ($user->getWritableColumns() as $column) {
            if (!isset($this->userData[$column])) {
                continue;
            }

            $user->set($column, $this->userData[$column]);
        }

        $save = $user->save();

        $this->errors = $user->validationErrors();

        if ($save) {
            $this->refreshSession();
        }

        return $save;
    }

    /**
     * Updates the user session.
     *
     * @return void
     */
    public function updateSession(): void
    {
        Session::set($this->getSessionKey(), $this->getSessionData());
    }
}
