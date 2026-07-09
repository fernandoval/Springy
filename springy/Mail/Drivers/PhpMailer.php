<?php

/**
 * Driver for use with PHPMailer class.
 *
 * @copyright 2025 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 * @license   https://github.com/springy-framework/core/blob/master/LICENSE MIT
 *
 * @see       https://github.com/PHPMailer/PHPMailer
 *
 * The PHPMailer library is not a dependency of this project.
 * This driver is implemented only as a facility to the developers.
 *
 * You must add the PHPMailer library as dependency of your project
 * by adding it in your project's composer.json file like this:
 *
 * {
 *   "require": {
 *     "phpmailer/phpmailer": "^6.10.0"
 *   }
 * }
 *
 * Or install it yourself with the following command line:
 *
 * $ composer require "phpmailer/phpmailer:^6.10.0"
 */

namespace Springy\Mail\Drivers;

use PHPMailer\PHPMailer\PHPMailer as PHPMailerDriver;
use Springy\Exceptions\SpringyException;

/**
 * Driver class for use with PHPMailer class.
 */
class PhpMailer implements MailDriverInterface
{
    private PHPMailerDriver $mail;
    private string $error;

    public function __construct(array $settings)
    {
        if (!isset($settings['protocol'])) {
            throw new SpringyException('Mail configuration "protocol" undefined');
        }

        $this->mail = new PHPMailerDriver(true);
        $this->mail->CharSet = config_get('main.charset', 'UTF-8');
        $this->error = '';

        $this->setProtocol($settings);
    }

    public function addAttachment(string $path, string $name = '', string $type = ''): void
    {
        $this->mail->addAttachment($path, $name, 'base64', $type);
    }

    public function addBcc(string $email, string $name = ''): void
    {
        $this->mail->addBcc($email, $name);
    }

    public function addCategory(string $category): void
    {
        throw new SpringyException('Mail categories not supported by this driver');
    }

    public function addCc(string $email, string $name = ''): void
    {
        $this->mail->addCc($email, $name);
    }

    public function addHeader(string $header, string $value): void
    {
        $this->mail->addCustomHeader($header, $value);
    }

    public function addTemplateVar(string $name, mixed $value): void
    {
        throw new SpringyException('Mail template variables not supported by this driver');
    }

    public function addTo(string $email, string $name = ''): void
    {
        $this->mail->addAddress($email, $name);
    }

    public function getLastError(): string
    {
        return $this->error;
    }

    public function send(): void
    {
        $this->error = '';

        try {
            if (!$this->mail->send()) {
                $this->error = $this->mail->ErrorInfo;
            }
        } catch (\Throwable $err) {
            $this->error = $err->getCode() . ': ' . $err->getMessage();
        }
    }

    public function setAlternativeBody(string $text): void
    {
        $this->mail->AltBody = $text;
    }

    public function setBody(string $body, bool $html = true): void
    {
        $html ? $this->mail->msgHTML($body) : $this->setAlternativeBody($body);
    }

    public function setFrom(string $email, string $name = ''): void
    {
        $this->mail->setFrom($email, $name);
    }

    /**
     * Sets the mail protocol.
     *
     * @param array $config
     *
     * @throws SpringyException
     */
    private function setProtocol(array $config): void
    {
        if ($config['protocol'] == 'smtp') {
            if (!isset($config['host'])) {
                throw new SpringyException('Mail configuration "host" undefined');
            }

            $this->mail->isSMTP();
            $this->mail->SMTPDebug = $config['debug'] ?? false;
            $this->mail->Debugoutput = $config['debugoutput'] ?? 'html';
            $this->mail->Host = $config['host'];
            $this->mail->Port = $config['port'] ?? 25;
            $this->mail->SMTPAuth = $config['authenticated'] ?? false;
            $this->mail->SMTPSecure = $config['cryptography'] ?? '';

            if ($this->mail->SMTPAuth) {
                $this->mail->Username = $config['username'] ?? '';
                $this->mail->Password = $config['password'] ?? '';
            }

            return;
        } elseif ($config['protocol'] == 'sendmail') {
            $this->mail->isSendmail();

            return;
        }

        throw new SpringyException('Unsuported mail protocol');
    }

    public function setSubject(string $subject): void
    {
        $this->mail->Subject = $subject;
    }

    public function setTemplateId(string $tid): void
    {
        throw new SpringyException('Mail templates not supported by this driver');
    }
}
