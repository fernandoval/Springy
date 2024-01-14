<?php

/**
 * Example model.
 */

namespace App\Models;

use Springy\DB\Where;
use Springy\Model;
use Springy\Validation\Validator;

class User extends Model
{
    public const TABLE_NAME = 'users';

    // Column names
    public const COL_ID = 'id';
    public const COL_EMAIL = 'email';
    public const COL_UUID = 'uuid';
    public const COL_PASSWD = 'password';
    public const COL_NAME = 'name';
    public const COL_CREATED_AT = 'created_at';
    public const COL_DELETED = 'deleted';

    protected $tableName = self::TABLE_NAME;
    protected $tableColumns = '*';
    protected $insertDateColumn = self::COL_CREATED_AT;
    protected $deletedColumn = self::COL_DELETED;
    protected $writableColumns = [
        self::COL_EMAIL,
        self::COL_UUID,
        self::COL_PASSWD,
        self::COL_NAME,
    ];
    protected $hookedColumns = [
        self::COL_EMAIL => 'trimEmail',
        self::COL_PASSWD => 'hashPass',
    ];
    protected $abortOnEmptyFilter = false;

    protected $permissions = [];

    /**
     * Returns the writable columns array.
     *
     * @return array
     */
    public function getWritableColumns(): array
    {
        return $this->writableColumns;
    }

    /**
     * Hook function to compute the password column.
     *
     * @param string $password the password in plain text mode.
     *
     * @return string the password hash.
     */
    protected function hashPass($password)
    {
        return (new \Springy\Security\BCryptHasher())->make($password);
    }

    /**
     * Remove trim trailing spaces and converts to lowercase from the value.
     *
     * @param string $value the value of the column.
     *
     * @return string
     */
    protected function trimEmail($value)
    {
        return trim(mb_strtolower($value));
    }

    /**
     * Trigger for validate register before insert it.
     *
     * You can verify if the email already exists in database.
     */
    protected function triggerBeforeInsert()
    {
        $where = new Where();
        $where->condition(self::COL_EMAIL, $this->get(self::COL_EMAIL));
        $where->condition($this->deletedColumn, 0, Where::OP_GREATER_EQUAL);
        $user = new self($where);

        if (!$user->isLoaded()) {
            return true;
        }

        $col = 'ok';
        $validator = Validator::make(
            [
                $col => null,
            ],
            [
                $col => Validator::V_REQUIRED,
            ],
            [
                $col => [
                    Validator::V_REQUIRED => 'Email already exists.',
                ],
            ]
        );
        $result = $validator->validate();
        $this->validationErrors = $validator->errors();

        return $result;
    }

    protected function validationRules()
    {
        return [
            self::COL_EMAIL => [
                Validator::V_REQUIRED,
                Validator::V_EMAIL,
            ],
            self::COL_UUID => [
                Validator::V_REQUIRED,
                Validator::V_REGEX => [
                    '/^\{?[0-9a-f]{8}\-?[0-9a-f]{4}\-?[0-9a-f]{4}\-?[0-9a-f]{4}\-?[0-9a-f]{12}\}?$/i',
                ],
            ],
            self::COL_PASSWD => [Validator::V_REQUIRED],
            self::COL_NAME => [
                Validator::V_REQUIRED,
                Validator::V_MAX_LENGTH => [150],
            ],
        ];
    }

    protected function validationErrorMessages()
    {
        return [
            self::COL_EMAIL => [
                Validator::V_REQUIRED => 'O email é obrigatório.',
                Validator::V_EMAIL => 'O email inserido não é válido.',
            ],
            self::COL_UUID => [
                Validator::V_REQUIRED => 'O UUID é obrigatório.',
                Validator::V_REGEX => 'O UUID inserido não é válido.',
            ],
            self::COL_PASSWD => [
                Validator::V_REQUIRED => 'A senha é obrigatória.',
            ],
            self::COL_NAME => [
                Validator::V_REQUIRED => 'O nome é obrigatório.',
                Validator::V_MAX_LENGTH => 'Nome grande demais.',
            ],
        ];
    }
}
