<?php

/**
 * Test case for Utils\Strings class.
 *
 * phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
 *
 * @copyright 2015 Fernando Val
 * @author    Allan Marques <allan.marques@ymail.com>
 * @author    Fernando Val <fernando.val@gmail.com>
 *
 * @version   1.0.0.3
 */

use PHPUnit\Framework\TestCase;
use Springy\Utils\Strings;

class StringsTest extends TestCase
{
    public function testEmailGetsValidateSuccessfully()
    {
        $this->assertTrue(Strings::validateEmailAddress('fernando@fval.com.br'));
        $this->assertTrue(Strings::validateEmailAddress('fernando@fval.com.br', false));

        $this->assertFalse(Strings::validateEmailAddress('fernando@fval', false));
        $this->assertFalse(Strings::validateEmailAddress('fernandofval.com.br', false));
        $this->assertFalse(Strings::validateEmailAddress('fernando@fval.nonexiuuste'));
        $this->assertTrue(Strings::validateEmailAddress('fernando@fval.nonexiuuste', false));
    }

    public function testCnpj()
    {
        $cnpj = '22.608.842/0001-63';
        $this->assertTrue(Strings::cnpj($cnpj));
    }
}
