<?php

/**	\file
 *	FVAL PHP Framework for Web Applications.
 *
 *  \copyright Copyright (c) 2007-2015 FVAL Consultoria e Informática Ltda.\n
 *  \copyright Copyright (c) 2007-2015 Fernando Val\n
 *	\copyright Copyright (c) 2014 Allan Marques
 *
 *	\brief     Test case for Classe com métodos para diversos tipos de tratamento e validação de dados string
 *	\warning   Este arquivo é parte integrante do framework e não pode ser omitido
 *	\version   0.1
 *  \author    Allan Marques  - allan.marques@ymail.com
 *	\ingroup   tests
 */
use FW\Utils\Strings;

class StringsTest extends PHPUnit_Framework_TestCase
{
    public function testThatADateDifferenceByDaysIsReturned()
    {
        $date1 = new \DateTime();

        $date2 = clone $date1;
        $date2->add(new \DateInterval('P3D'));

        $expected = 3;

        $actual = Strings::qtdDias($date1->format('d/m/Y'), $date2->format('d/m/Y'));

        $this->assertEquals($expected, $actual);

        $actual = Strings::qtdDias($date1->format('Y-m-d'), $date2->format('Y-m-d'));

        $this->assertEquals($expected, $actual);
    }

    public function testEmailGetsValidateSuccessfully()
    {
        $this->assertTrue(Strings::validateEmailAddress('fernando@fval.com.br'));
        $this->assertTrue(Strings::validateEmailAddress('fernando@fval.com.br', false));

        $this->assertFalse(Strings::validateEmailAddress('fernando@fval', false));
        $this->assertFalse(Strings::validateEmailAddress('fernandofval.com.br', false));
        $this->assertFalse(Strings::validateEmailAddress('fernando@fval.nonexiuuste'));
        $this->assertFalse(Strings::validateEmailAddress('fernando@fval.nonexiuuste', false));
    }

    public function testThatDateGetsValidatedSuccessfully()
    {
        $this->assertTrue(Strings::data('25/01/1987'));
        $this->assertFalse(Strings::data('31d/f02/gg2014'));
        $this->assertFalse(Strings::data('31/02/2014'));
    }
}
