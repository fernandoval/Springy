<?php
/**	\file
 *  Springy.
 *
 *  \brief      Class driver for use with SendGrid v5 class for integration with SendGrid API v3.
 *  \copyright  ₢ 2007-2016 Fernando Val
 *  \author     Fernando Val - fernando.val@gmail.com
 *  \see        https://github.com/sendgrid/sendgrid-php
 *  \version    3.0.5
 *  \ingroup    framework
 */
namespace Springy\Mail;

use Springy\Configuration;
use Springy\Errors;

/**
 *  \brief Driver class for sent mail using SendGrid v5 official class.
 *
 *  \note This classe is a driver used by Springy\Mail classe.
 *        Do not use it directly.
 */
class SendGridDriver implements MailDriverInterface
{
    private $sendgrid = null;
    private $mailObj = null;

    /**
     *  \brief Constructor method
     *  \param $cfg - array with de configuration.
     */
    public function __construct($cfg)
    {
        if (!isset($cfg['apikey']) || empty($cfg['apikey'])) {
            throw new \Exception('Mail configuration SendGrid authentication undefined');
        }

        $options = (isset($cfg['options']) && is_array($cfg['options'])) ? $cfg['options'] : [];

        $this->sendgrid = new \SendGrid($cfg['apikey'], $options);
        $this->mailObj = new \SendGrid\Mail();
        $this->mailObj->addPersonalization(new \SendGrid\Personalization());

        if (Configuration::get('mail', 'errors_go_to')) {
            $this->mailObj->personalization[0]->addHeader('Errors-To', Configuration::get('mail', 'errors_go_to'));
        }
    }

    /**
     *  \brief Add a standard email message header.
     */
    public function addHeader($header, $value)
    {
        $this->mailObj->personalization[0]->addHeader($header, $value);
    }

    /**
     *  \brief Add an address to 'To' field.
     *
     *  \param $email - the email address
     *  \param $name - the name of the person (optional)
     */
    public function addTo($email, $name = '')
    {
        $this->mailObj->personalization[0]->addTo(new \SendGrid\Email($name, $email));
    }

    /**
     *  \brief Add an address to 'BCC' field.
     *
     *  \param $email - the email address
     *  \param $name - the name of the person (optional)
     */
    public function addBCC($email, $name = '')
    {
        $this->mailObj->personalization[0]->addBcc(new \SendGrid\Email($name, $email));
    }

    /**
     *  \brief Add an address to 'CC' field.
     *
     *  \param $email - the email address
     *  \param $name - the name of the person (optional)
     */
    public function addCC($email, $name = '')
    {
        $this->mailObj->personalization[0]->addCc(new \SendGrid\Email($name, $email));
    }

    /**
     *  \brief Add a file to be attached to the e-mail.
     *
     *  \param $path - full pathname to the attachment
     *  \param $name - override the attachment name (optional)
     *  \param $type - MIME type/file extension type (optional)
     *  \param $encoding - file enconding (optional)
     */
    public function addAttachment($path, $name = '', $type = '', $encoding = 'base64')
    {
        $attachment = new \SendGrid\Attachment();
        $attachment->setContent(base64_encode(file_get_contents($path)));
        $attachment->setType($type);
        $attachment->setFilename($name);
        $attachment->setContentId(md5(uniqid(rand(), true)));
        $attachment->setDisposition('attachment');

        $this->mailObj->addAttachment($attachment);
    }

    /**
     *  \brief Set the 'From' field.
     *
     *  \param $email - the email address
     *  \param $name - the name of the person (optional)
     */
    public function setFrom($email, $name = '')
    {
        $this->mailObj->setFrom(new \SendGrid\Email($name, $email));
    }

    /**
     *  \brief Set the mail subject.
     *
     *  \param $subject - the subject text
     */
    public function setSubject($subject)
    {
        $this->mailObj->setSubject($subject);
        $this->mailObj->personalization[0]->setSubject($subject);
    }

    /**
     *  \brief Set the message bo.
     *
     *  \param $body - HTML ou text message body
     *  \param $html - set true if body is HTML ou false if plain text
     */
    public function setBody($body, $html = true)
    {
        if ($html) {
            $this->mailObj->addContent(new \SendGrid\Content('text/html', $body));
        } else {
            $this->mailObj->addContent(new \SendGrid\Content('text/plain', $body));
        }
    }

    /**
     *	\brief Set the alternative plain-text message body for old message readers.
     */
    public function setAlternativeBody($text)
    {
        $this->mailObj->addContent(new \SendGrid\Content('text/plain', $text));
    }

    /**
     *  \brief Set a template for this email.
     */
    public function setTemplate($name)
    {
        $this->mailObj->setTemplateId($name);
    }

    /**
     *  \brief Add value to a template variable.
     */
    public function addTemplateVar($name, $value)
    {
        $this->mailObj->personalization[0]->addSubstitution($name, $value);
    }

    /**
     *  \brief Send the mail message
     *  \return The error message or a empty string if success.
     */
    public function send()
    {
        $error = false;

        try {
            $response = $this->sendgrid->client->mail()->send()->post($this->mailObj);
            $error = $response->body();
        } catch (\Exception $e) {
            $error = $e->getCode().' - '.$e->getMessage().' at '.$e->getFile().' ('.$e->getLine().')';
        }

        return $error;
    }
}
