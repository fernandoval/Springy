<?php

/**
 * Standard hasher interface.
 *
 * @copyright 2016 Fernando Val
 * @author    Allan Marques <allan.marques@ymail.com>
 * @author    Fernando Val <fernando.val@gmail.com>
 *
 * @version   0.1.3
 */

namespace Springy\Security;

interface HasherInterface
{
    /**
     * Makes and returns the hash string.
     *
     * @param string $stringToHash
     * @param int    $times
     *
     * @return string
     */
    public function make($stringToHash, $times);

    /**
     * Checks the string against the hash.
     *
     * @param string $stringToCheck
     * @param string $hash
     *
     * @return bool
     */
    public function verify($stringToCheck, $hash);

    /**
     * Checks if the string needs to be re-encrypted.
     *
     * @param string $hash
     * @param int    $times
     *
     * @return bool
     */
    public function needsRehash($hash, $times);
}
