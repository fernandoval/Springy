<?php

/**
 * Test case for Mail\Driver\SendGrid class.
 *
 * phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
 *
 * @copyright 2025 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 */

use PHPUnit\Framework\TestCase;
use Springy\Mail\Drivers\SendGrid;

class SendGridTest extends TestCase
{
    public $sendgrid;

    protected function setUp(): void
    {
        $this->sendgrid = new SendGrid([
            'apikey' => 'your_sendgrid_api_key',
            'options' => [
                'protocol' => 'https',
                'raise_exceptions' => false,
                'turn_off_ssl_verification' => false,
            ],
        ]);
    }

    public function testThatSendGridCanBeInstantiated()
    {
        $this->assertInstanceOf(SendGrid::class, $this->sendgrid);
    }

    public function testCallingMethods()
    {
        // $this->expectNotToPerformAssertions();
        $this->sendgrid->addAttachment(__FILE__, 'test.php', 'text/plain');
        $this->sendgrid->addBcc('to.bcc@example.com', 'Secret Name');
        $this->sendgrid->addCategory('test');
        $this->sendgrid->addCc('to.copy@example.com', 'Copy Name');
        $this->sendgrid->addHeader('Errors-To', 'errors.to@example.com');
        $this->sendgrid->addTemplateVar('var', 'value');
        $this->sendgrid->addTo('to.mail@example.com', 'To Name');
        $this->sendgrid->setAlternativeBody('Alternative body');
        $this->sendgrid->setBody('<h1>HTML body</h1>', true);
        $this->sendgrid->setFrom('send.from@example.com', 'Sender Name');
        $this->sendgrid->setSubject('Test SendGrid');
        $this->sendgrid->setTemplateId('id_of_template');
        $this->assertIsString($this->sendgrid->getLastError());
    }
}
