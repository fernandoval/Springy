<?php

/**
 * Test case for Utils\MessageContainer class.
 *
 * phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
 *
 * @copyright 2015 Fernando Val
 * @author    Allan Marques <allan.marques@ymail.com>
 * @author    Fernando Val <fernando.val@gmail.com>
 *
 * @version   1.0.0.2
 */

use PHPUnit\Framework\TestCase;
use Springy\Utils\MessageContainer;

class MessageContainerTest extends TestCase
{
    protected $msgContainer;

    protected function setUp(): void
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
