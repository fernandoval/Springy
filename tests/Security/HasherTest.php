<?php
/** \file
 *  Springy
 *
 *  \brief      Test case for Classe pa geração de hashes via BCrypt.
 *  \copyright  Copyright (c) 2007-2015 Fernando Val
 *  \author     Allan Marques - allan.marques@ymail.com
 *  \warning    Este arquivo é parte integrante do framework e não pode ser omitido
 *  \version    0.1.1
 *  \note       Esta biblioteca utiliza como dependência a classe password_compat de Anthony Ferrara.
 *  \ingroup    tests
 */
use Springy\Security\BCryptHasher as Hasher;

class HasherTest extends PHPUnit_Framework_TestCase
{
    public $hasher;

    public function setUp()
    {
        $this->hasher = new Hasher();
    }

    public function testThatHasherCanGenerateASecureHash()
    {
        $hash = $this->hasher->make('password');

        $this->assertGreaterThanOrEqual(60, strlen($hash));
    }

    public function testThatHasherCanVerifyTheHashedString()
    {
        $hash = $this->hasher->make('password');

        $this->assertTrue($this->hasher->verify('password', $hash));
    }

    public function testThatHasherTellsIfAHashNeedsRehashing()
    {
        $hash = $this->hasher->make('password', 5);

        $this->assertTrue($this->hasher->needsRehash($hash, 10));
    }
}
