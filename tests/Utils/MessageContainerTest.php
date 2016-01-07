<?php
/**	\file
 *	FVAL PHP Framework for Web Applications.
 *
 *  \copyright Copyright (c) 2007-2015 FVAL Consultoria e Informática Ltda.\n
 *  \copyright Copyright (c) 2007-2015 Fernando Val\n
 *	\copyright Copyright (c) 2014 Allan Marques
 *
 *	\brief     Test case for Classe container de mensagens de texto
 *	\warning   Este arquivo é parte integrante do framework e não pode ser omitido
 *	\version   0.1
 *  \author    Allan Marques - allan.marques@ymail.com
 *	\ingroup   tests
 */
use FW\Utils\MessageContainer;

class MessageContainerTest extends PHPUnit_Framework_TestCase
{
    protected $msgContainer;

    public function setUp()
    {
        $this->msgContainer = new MessageContainer();
    }

    public function testMessageGetsFormated()
    {
        $this->msgContainer->setMessages(['errors' => 'Erro!']);
        $msg = $this->msgContainer->get('errors', '<li>:msg</li>');

        $this->assertEquals(['<li>Erro!</li>'], $msg);
    }

    public function testMultipleMessagesGetsFormated()
    {
        $this->msgContainer->add('errors', 'Erro1');
        $this->msgContainer->add('errors', 'Erro2');
        $this->msgContainer->add('errors', 'Erro3');

        $msg = $this->msgContainer->get('errors', '<li>:msg</li>');

        $this->assertEquals(['<li>Erro1</li>', '<li>Erro2</li>', '<li>Erro3</li>'], $msg);
    }

    public function testGetJustFirstMessageOfAType()
    {
        $this->msgContainer->add('errors', 'Erro1');
        $this->msgContainer->add('errors', 'Erro2');
        $this->msgContainer->add('errors', 'Erro3');

        $msg = $this->msgContainer->first('errors', '<li>:msg</li>');

        $this->assertEquals('<li>Erro1</li>', $msg);
    }

    public function testGetsAllMessages()
    {
        $this->msgContainer->add('errors', 'Erro');
        $this->msgContainer->add('success', 'Success');
        $this->msgContainer->add('warning', 'Warning');

        $msg = $this->msgContainer->all('<li>:msg</li>');

        $this->assertEquals(
            [
                '<li>Erro</li>',
                '<li>Success</li>',
                '<li>Warning</li>',
            ],
            $msg
        );
    }
}
