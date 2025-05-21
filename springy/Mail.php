<?php

/**
 * Email sender.
 *
 * @copyright 2007-2016 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 * @license   https://github.com/fernandoval/Springy/blob/master/LICENSE MIT
 *
 * @deprecated 4.7.0
 */

namespace Springy;

use Springy\Exceptions\SpringyException;
use Springy\Mail\Drivers\PhpMailer;
use Springy\Mail\Drivers\SendGrid;
use Springy\Mail\Mailer;

class Mail
{
    private Mailer $newMailer;

    public function __construct($mailer = null)
    {
        $driver = $mailer ?? config_get('mail.default_driver');

        if (is_null($driver)) {
            $this->newMailer = new Mailer();

            return;
        };

        $cfg = config_get('mail.mailers.' . $driver)
            ?? throw new SpringyException('Mail configuration \'mailers.' . $driver . '\' undefined');

        match ($cfg['driver'] ?? 'undefined') {
            'phpmailer' => $this->startPhpMailer($cfg),
            'sendgrid' => $this->startSendGrid($cfg),
            'swiftmailer' => throw new SpringyException('SwiftMailer driver not implemented anymore'),
            'mimemessage' => throw new SpringyException('MimeMessage driver not implemented anymore'),
            'undefined' => throw new SpringyException('Mimee driver undefined'),
            default => throw new SpringyException('Mail configuration driver unsupported'),
        };

        $this->newMailer = new Mailer();
    }

    private function startPhpMailer(array $cfg): void
    {
        config_set('mail.driver', PhpMailer::class);
        config_set('mail.settings', $cfg);
    }

    private function startSendGrid(array $cfg): void
    {
        config_set('mail.driver', SendGrid::class);
        config_set('mail.settings', $cfg);
    }

    /**
     * Adds a standard email message header.
     */
    public function addHeader($header, $value)
    {
        $this->newMailer->addHeader($header, $value);
    }

    /**
     * Define o valor de um item de cabeçalho.
     */
    public function setHeader($header, $value)
    {
        $this->addHeader($header, $value);
    }

    /**
     * Sets a template for the email.
     */
    public function setTemplate($name)
    {
        $this->newMailer->setTemplateId($name);
    }

    /**
     * Adds value to a template variable.
     */
    public function addTemplateVar($name, $value)
    {
        $this->newMailer->addTemplateVar($name, $value);
    }

    /**
     * Define o valor do campo To.
     *
     * @param string $email o endereço de email do destinatário ou um array
     *                      contendo a lista de destinatários, no seguinte
     *                      formato: ['email1@dominio.com' => 'Nome 1',
     *                      'email2@dominio.com' => 'Nome 2']
     * @param string $name  o nome do destinatário.
     *
     * Obs.: Caso seja passado um array de emails para $email, o valor de
     * $name será ignorado.
     */
    public function to($email, $name = '')
    {
        if (is_array($email)) {
            foreach ($email as $mail => $name) {
                $this->newMailer->addTo($mail, $name);
            }

            return;
        }

        $this->newMailer->addTo($email, $name);
    }

    /**
     * Define o valor do campo Cc.
     */
    public function cc($email, $name = '')
    {
        if (is_array($email)) {
            foreach ($email as $mail => $name) {
                $this->newMailer->addCC($mail, $name);
            }

            return;
        }

        $this->newMailer->addCC($email, $name);
    }

    /**
     * Define o valor do campo Bcc.
     */
    public function bcc($email, $name = '')
    {
        if (is_array($email)) {
            foreach ($email as $mail => $name) {
                $this->newMailer->addBCC($mail, $name);
            }

            return;
        }

        $this->newMailer->addBCC($email, $name);
    }

    /**
     * Define o valor do campo From.
     */
    public function from($email, $name = '')
    {
        $this->newMailer->setFrom($email, $name);
    }

    /**
     * Define o valor do campo Subject.
     */
    public function subject($subject)
    {
        $this->newMailer->setSubject($subject);
    }

    /**
     * Monta o corpo da mensagem.
     */
    public function body($html = '', $text = '')
    {
        if ($text) {
            $this->newMailer->setAlternativeBody($text);
        }
        if ($html) {
            $this->newMailer->setBody($html, true);
        }
    }

    /**
     * Adiciona um anexo ao e-mail.
     */
    public function addAttach($path, $name = '', $type = '', $encoding = 'base64')
    {
        $this->newMailer->addAttachment($path, $name, $type, $encoding);
    }

    /**
     * Adds a category to the e-mail.
     *
     * @param string $category
     */
    public function addCategory($category)
    {
        $this->newMailer->addCategory($category);
    }

    /**
     * Sends the message.
     */
    public function send()
    {
        return $this->newMailer->send()->getLastError();
    }

    /**
     * Sends a menssage.
     *
     * @param string $from        email from address.
     * @param string $from_name   email from name.
     * @param string $mailto      email to address.
     * @param string $to_name     email to name.
     * @param string $subject     subject.
     * @param string $htmlmessage HTML formated body.
     * @param string $textmessage plain text body.
     *
     * @return mixed
     */
    public function sendMessage($from, $from_name, $mailto, $to_name, $subject, $htmlmessage, $textmessage)
    {
        $this->from($from, $from_name);
        $this->to($mailto, $to_name);
        $this->subject($subject);
        $this->body($htmlmessage, $textmessage);

        return $this->send();
    }
}
