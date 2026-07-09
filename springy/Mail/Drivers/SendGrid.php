<?php

/**
 * Driver for use with SendGrid v7 class for integration with SendGrid API v3.
 *
 * @copyright 2015 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 * @license   https://github.com/springy-framework/core/blob/master/LICENSE MIT
 *
 * @see       https://github.com/sendgrid/sendgrid-php
 *
 * The SendGrid PHP SDK library is not a dependency of this project.
 * This driver is implemented only as a facility to the developers.
 *
 * You must add the SendGrid PHP SDK library as dependency of your project
 * by adding it in your project's composer.json file like this:
 *
 * {
 *   "require": {
 *     "sendgrid/sendgrid": "~7"
 *   }
 * }
 *
 * Or install it yourself with the following command line:
 *
 * $ composer require "sendgrid/sendgrid:~7"
 */

namespace Springy\Mail\Drivers;

use SendGrid as SendGridAPI;
use SendGrid\Mail\Mail;
use Springy\Exceptions\SpringyException;
use Throwable;

class SendGrid implements MailDriverInterface
{
    private SendGridAPI $sendgrid; // the SendGrid API object
    private Mail $mail; // the SendGrid mail transport object
    private string $error;

    public function __construct(array $settings)
    {
        $apikey = $settings['apikey'] ?? false;
        $options = $settings['options'] ?? [];

        if (!$apikey) {
            throw new SpringyException('SendGrid API key undefined');
        } elseif (!is_array($options)) {
            throw new SpringyException('Invalid SendGrid configuration options');
        }

        $this->sendgrid = new SendGridAPI($apikey, $options);
        $this->mail = new Mail();
        $this->error = '';
    }

    public function addAttachment(string $path, string $name = '', string $type = ''): void
    {
        $fileEncoded = file_get_contents($path);
        $this->mail->addAttachment($fileEncoded, $type, $name);
    }

    public function addBcc(string $email, string $name = ''): void
    {
        $this->mail->addBcc($email, $name);
    }

    public function addCategory(string $category): void
    {
        $this->mail->addCategory($category);
    }

    public function addCc(string $email, string $name = ''): void
    {
        $this->mail->addCc($email, $name);
    }

    public function addHeader(string $header, string $value): void
    {
        $this->mail->addHeader($header, $value);
    }

    public function addTemplateVar(string $name, mixed $value): void
    {
        $this->mail->addSubstitution($name, $value);
    }

    public function addTo(string $email, string $name = ''): void
    {
        $this->mail->addTo($email, $name);
    }

    public function getLastError(): string
    {
        return $this->error;
    }

    public function send(): void
    {
        $this->error = '';

        try {
            $response = $this->sendgrid->send($this->mail);
            $this->error = $response->body();
        } catch (Throwable $err) {
            $this->error = $err->getCode()
                . ' - ' . $err->getMessage()
                . ' at ' . $err->getFile()
                . ' (' . $err->getLine() . ')';
        }
    }

    public function setAlternativeBody(string $text): void
    {
        $this->mail->addContent('text/plain', $text);
    }

    public function setBody(string $body, bool $html = true): void
    {
        $this->mail->addContent($html ? 'text/html' : 'text/plain', $body);
    }

    public function setFrom(string $email, string $name = ''): void
    {
        $this->mail->setFrom($email, $name);
    }

    public function setSubject(string $subject): void
    {
        $this->mail->setSubject($subject);
    }

    public function setTemplateId(string $tid): void
    {
        $this->mail->setTemplateId($tid);
    }
}
