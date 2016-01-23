<?php
/**	\file
 *  FVAL PHP Framework for Web Applications.
 *
 *  \copyright  Copyright (c) 2007-2016 FVAL Consultoria e Informática Ltda.\n
 *  \copyright  Copyright (c) 2007-2016 Fernando Val\n
 *
 *  \brief      Class driver for use with SendGrid class
 *  \see        https://github.com/sendgrid/sendgrid-php
 *  \warning    This file is part of the framework and can not be omitted
 *  \version    1.0.0
 *  \author     Fernando Val - fernando.val@gmail.com
 *  \ingroup    framework
 */
namespace FW\Mail;

use FW\Configuration;
use FW\Errors;

/**
 *  \brief Driver class for sent mail using SendGrid official class.
 *
 *  \note This classe is a driver used by FW\Mail classe.
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
        if (!isset($cfg['apikey']) && !isset($cfg['username']) && !isset($cfg['password'])) {
            throw new \Exception('Mail configuration SendGrid authentication undefined');
        }

        $options = (isset($cfg['options']) && is_array($cfg['options'])) ? $cfg['options'] : [];

        if (isset($cfg['apikey']) && $cfg['apikey']) {
            $this->sendgrid = new \SendGrid($cfg['apikey'], $options);
        } else {
            $this->sendgrid = new \SendGrid($cfg['username'], $cfg['password'], $options);
        }
        $this->mailObj = new \SendGrid\Email();

        if (Configuration::get('mail', 'errors_go_to')) {
            $this->mailObj->addHeader('Errors-To', Configuration::get('mail', 'errors_go_to'));
        }
    }

    /**
     *  \brief Add a standard email message header.
     */
    public function addHeader($header, $value)
    {
        $this->mailObj->addHeader($header, $value);
    }

    /**
     *  \brief Add an address to 'To' field.
     *  
     *  \param $email - the email address
     *  \param $name - the name of the person (optional)
     */
    public function addTo($email, $name = '')
    {
        $this->mailObj->addTo($email, $name);
    }

    /**
     *  \brief Add an address to 'BCC' field.
     *  
     *  \param $email - the email address
     *  \param $name - the name of the person (optional)
     */
    public function addBCC($email, $name = '')
    {
        $this->mailObj->addBcc($email, $name);
    }

    /**
     *  \brief Add an address to 'CC' field.
     *  
     *  \param $email - the email address
     *  \param $name - the name of the person (optional)
     */
    public function addCC($email, $name = '')
    {
        $this->mailObj->addCc($email, $name);
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
        $this->mailObj->addAttachment($path);
    }

    /**
     *  \brief Set the 'From' field.
     *  
     *  \param $email - the email address
     *  \param $name - the name of the person (optional)
     */
    public function setFrom($email, $name = '')
    {
        $this->mailObj->setFrom($email);
        $this->mailObj->setFromName($name);
    }

    /**
     *  \brief Set the mail subject.
     *  
     *  \param $subject - the subject text
     */
    public function setSubject($subject)
    {
        $this->mailObj->setSubject($subject);
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
            $this->mailObj->setHtml($body);
        } else {
            $this->mailObj->setText($body);
        }
    }

    /**
     *	\brief Set the alternative plain-text message body for old message readers.
     */
    public function setAlternativeBody($text)
    {
        $this->mailObj->setText($text);
    }

    /**
     *  \brief Send the mail message
     *  \return The error message or a empty string if success.
     */
    public function send()
    {
        $error = false;

        try {
            $this->sendgrid->send($this->mailObj);
            $error = '';
        } catch (\SendGrid\Exception $e) {
            $error = $e->getCode()."\n";
            foreach ($e->getErrors() as $er) {
                $error .= $er."\n";
            }
        }

        return $error;
    }
}
