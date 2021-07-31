<?php

/**
 * BCrypt hash generator class.
 *
 * @copyright 2014 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 * @author    Allan Marques <allan.marques@ymail.com>
 * @license   https://github.com/fernandoval/Springy/blob/master/LICENSE MIT
 *
 * @version 1.0.0
 *
 * @depends This class uses the password_compat class of Anthony Ferrara as a dependency.
 */

namespace Springy\Security;

/**
 * BCrypt hash generator class.
 */
class BCryptHasher implements HasherInterface
{
    protected $algorithm;
    protected $salt;

    /**
     * Constructor.
     *
     * @param int|string $algorithm
     * @param string     $salt
     */
    public function __construct($algorithm = PASSWORD_DEFAULT, $salt = '')
    {
        $this->algorithm = $algorithm;
        $this->salt = $salt;
    }

    /**
     * Make and returns a hash for given string.
     *
     * @param string $stringToHash
     * @param int    $times
     *
     * @return string
     */
    public function make($stringToHash, $times = 10)
    {
        return password_hash($stringToHash, $this->algorithm, $this->options($times));
    }

    /**
     * Checks the string against hash.
     *
     * @param string $stringToCheck
     * @param string $hash
     *
     * @return bool
     */
    public function verify($stringToCheck, $hash): bool
    {
        return password_verify($stringToCheck, $hash);
    }

    /**
     * Checks whether the hash must be rebuilt.
     *
     * @param string $hash
     * @param int    $times
     *
     * @return bool.
     */
    public function needsRehash($hash, $times = 10): bool
    {
        return password_needs_rehash($hash, $this->algorithm, $this->options($times));
    }

    /**
     * Returns an option array to BCrypt hash function.
     *
     * @param int $times
     *
     * @return array
     */
    protected function options($times): array
    {
        $options = ['cost' => $times];

        if ($this->salt) {
            $options['salt'] = $this->salt;
        }

        return $options;
    }
}
