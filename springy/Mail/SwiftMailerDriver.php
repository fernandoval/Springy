<?php

/**
 * Class driver for use with Swift Mailer class.
 *
 * http://swiftmailer.org/
 *
 * @copyright 2007 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 * @license   https://github.com/fernandoval/Springy/blob/master/LICENSE MIT
 *
 * @version   2.1.10
 */

namespace Springy\Mail;

use Springy\Configuration;

/**
 * Driver class for sent mail using Swift Mailer class.
 *
 * This classe is a driver used by Springy\Mail classe.
 * Do not use it directly.
 */
class SwiftMailerDriver implements MailDriverInterface
{
    private $mailObj = null;

    /**
     * Constructor method.
     *
     * @param array $cfg
     */
    public function __construct($cfg)
    {
        if (!isset($cfg['protocol'])) {
            throw new \Exception('Mail configuration \'protocol\' undefined');
        }

        $this->mailObj = \Swift_Message::newInstance();
        $this->mailObj->setCharset(charset());

        if ($cfg['protocol'] == 'smtp') {
            if (!isset($cfg['host'])) {
                throw new \Exception('Mail configuration \'host\' undefined');
            }

            $this->transport = \Swift_SmtpTransport::newInstance($cfg['host'], isset($cfg['port']) ? $cfg['port'] : 25);

            if (isset($cfg['cryptography'])) {
                $this->transport->setEncryption($cfg['cryptography']);
            }
            if (isset($cfg['username']) && $cfg['username']) {
                $this->transport->setUsername($cfg['username']);
                $this->transport->setPassword(isset($cfg['password']) ? $cfg['password'] : '');
            }
        } elseif ($cfg['protocol'] == 'sendmail') {
            $this->transport = \Swift_SendmailTransport::newInstance(
                isset($cfg['sendmail_path']) ? $cfg['sendmail_path'] : null
            );
        } elseif ($cfg['protocol'] == 'mail') {
            $this->transport = \Swift_MailTransport::newInstance();
        } else {
            throw new \Exception('Unsuported mail transport agent');
        }

        if (Configuration::get('mail', 'errors_go_to')) {
            $this->mailObj->setReturnPath(Configuration::get('mail', 'errors_go_to'));
        }
        $this->mailObj->addTextHeader('Errors-To', Configuration::get('mail', 'errors_go_to'));
    }

    /**
     * Add a standard email message header.
     */
    public function addHeader($header, $value)
    {
        $this->mailObj->addTextHeader($header, $value);
    }

    public function addTo($email, $name = '')
    {
        $this->mailObj->addTo($email, $name);
    }

    public function addBCC($email, $name = '')
    {
        $this->mailObj->addBcc($email, $name);
    }

    public function addCC($email, $name = '')
    {
        $this->mailObj->addCc($email, $name);
    }

    public function addAttachment($path, $name = '', $type = '', $encoding = 'base64')
    {
        $attachment = \Swift_Attachment::fromPath($path, $type);
        if ($name) {
            $attachment->setFilename($name);
        }
        $this->mailObj->attach($attachment);
    }

    public function addCategory($category)
    {
        throw new \Exception('Resourse unavailable.');
    }

    public function setSubject($subject)
    {
        $this->mailObj->setSubject($subject);
    }

    public function setFrom($email, $name = '')
    {
        $this->mailObj->setFrom([$email => $name]);
    }

    public function setBody($body, $html = true)
    {
        $this->mailObj->setBody($body, $html ? 'text/html' : 'text/plain');
    }

    public function setAlternativeBody($text)
    {
        $this->mailObj->addPart($text, 'text/plain');
    }

    public function setTemplate($name)
    {
        throw new \Exception('Resourse unavailable.');
    }

    public function addTemplateVar($name, $value)
    {
        throw new \Exception('Resourse unavailable.');
    }

    public function send()
    {
        $error = false;

        $mailer = \Swift_Mailer::newInstance($this->transport);
        if (!$mailer->send($this->mailObj, $failures)) {
            $error = $failures;
        }

        return $error;
    }
}
