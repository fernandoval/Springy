<?php

/**
 * Test case for Mail\Mailer class.
 *
 * phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
 *
 * @copyright 2025 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 */

use PHPUnit\Framework\TestCase;
use Springy\Mail\Mailer;

class MailerTest extends TestCase
{
    public $mailer;

    protected function setUp(): void
    {
        $this->mailer = new Mailer();
    }

    public function testThatSendGridCanBeInstantiated()
    {
        $this->assertInstanceOf(Mailer::class, $this->mailer);
    }

    public function testCallingMethods()
    {
        // $this->expectNotToPerformAssertions();
        $this->mailer
            ->addAttachment(__FILE__, 'test.php', 'text/plain')
            ->addBcc('to.bcc@example.com', 'Secret Name')
            ->addCategory('test')
            ->addCc('to.copy@example.com', 'Copy Name')
            ->addHeader('Errors-To', 'errors.to@example.com')
            ->addTemplateVar('var', 'value')
            ->addTo('to.mail@example.com', 'To Name')
            ->setAlternativeBody('Alternative body')
            ->setBody('<h1>HTML body</h1>', true)
            ->setFrom('send.from@example.com', 'Sender Name')
            ->setSubject('Test Mailer')
            ->setTemplateId('id_of_template');
        $this->assertIsString($this->mailer->getLastError());
    }
}
