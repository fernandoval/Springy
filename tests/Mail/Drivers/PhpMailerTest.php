<?php

/**
 * Test case for Mail\Driver\PhpMailer class.
 *
 * phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
 *
 * @copyright 2025 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 */

use PHPUnit\Framework\TestCase;
use Springy\Exceptions\SpringyException;
use Springy\Mail\Drivers\PhpMailer;

class PhpMailerTest extends TestCase
{
    public $mailer;

    protected function setUp(): void
    {
        $this->mailer = new PhpMailer([
            'protocol' => 'smtp',
            'host' => 'your.smtp.host.addr',
            'port' => 587,
            'cryptography' => 'tls',
            'authenticated' => false,
        ]);
    }

    public function testThatSendGridCanBeInstantiated()
    {
        $this->assertInstanceOf(PhpMailer::class, $this->mailer);
    }

    public function testCallingMethods()
    {
        // $this->expectNotToPerformAssertions();
        $this->mailer->addAttachment(__FILE__, 'test.php', 'text/plain');
        $this->mailer->addBcc('to.bcc@example.com', 'Secret Name');
        $this->mailer->addCc('to.copy@example.com', 'Copy Name');
        $this->mailer->addHeader('Errors-To', 'errors.to@example.com');
        $this->mailer->addTo('to.mail@example.com', 'To Name');
        $this->mailer->setAlternativeBody('Alternative body');
        $this->mailer->setBody('<h1>HTML body</h1>', true);
        $this->mailer->setFrom('send.from@example.com', 'Sender Name');
        $this->mailer->setSubject('Test PHP Mailer');
        $this->assertIsString($this->mailer->getLastError());
    }

    public function testUnsupportedCategory()
    {
        $this->expectException(SpringyException::class);
        $this->mailer->addCategory('test');
    }

    public function testUnsupportedTemplateVar()
    {
        $this->expectException(SpringyException::class);
        $this->mailer->addTemplateVar('var', 'value');
    }

    public function testUnsupportedTemplateId()
    {
        $this->expectException(SpringyException::class);
        $this->mailer->setTemplateId('id_of_template');
    }
}
