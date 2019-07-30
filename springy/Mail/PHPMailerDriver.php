<?php
/**	\file
 *  Springy.
 *
 *  \brief      Class driver for use with PHPMailer class.
 *  \copyright  ₢ 2007-2016 Fernando Val
 *  \author     Fernando Val - fernando.val@gmail.com
 *  \see        https://github.com/PHPMailer/PHPMailer
 *  \version    1.1.0.7
 *  \ingroup    framework
 */

namespace Springy\Mail;

use Springy\Configuration;
use Springy\Kernel;

/**
 *  \brief Driver class for sent mail using PHPMailer class.
 *
 *  \note This classe is a driver used by Springy\Mail classe.
 *        Do not use it directly.
 */
class PHPMailerDriver implements MailDriverInterface
{
    private $mailObj = null;

    /**
     *  \brief Constructor method
     *  \param $cfg - array with de configuration.
     */
    public function __construct($cfg)
    {
        if (!isset($cfg['protocol'])) {
            throw new \Exception('Mail configuration \'protocol\' undefined');
        }

        $this->mailObj = new \PHPMailer();
        $this->mailObj->CharSet = Kernel::charset();

        if ($cfg['protocol'] == 'smtp') {
            if (!isset($cfg['host'])) {
                throw new \Exception('Mail configuration \'host\' undefined');
            }

            $this->mailObj->isSMTP();
            $this->mailObj->SMTPDebug = isset($cfg['debug']) ? $cfg['debug'] : false;
            $this->mailObj->Debugoutput = isset($cfg['debugoutput']) ? $cfg['debugoutput'] : 'html';
            $this->mailObj->Host = $cfg['host'];
            $this->mailObj->Port = isset($cfg['port']) ? $cfg['port'] : 25;
            $this->mailObj->SMTPSecure = isset($cfg['cryptography']) ? $cfg['cryptography'] : '';
            $this->mailObj->SMTPAuth = isset($cfg['authenticated']) ? $cfg['authenticated'] : false;
            if ($this->mailObj->SMTPAuth) {
                $this->mailObj->Username = isset($cfg['username']) ? $cfg['username'] : '';
                $this->mailObj->Password = isset($cfg['password']) ? $cfg['password'] : '';
            }
        } elseif ($cfg['protocol'] == 'sendmail') {
            $this->mailObj->isSendmail();
        } else {
            throw new \Exception('Unsuported mail protocol');
        }

        if (Configuration::get('mail', 'errors_go_to')) {
            $this->Sender = Configuration::get('mail', 'errors_go_to');
        }
        $this->mailObj->addCustomHeader('Errors-To', Configuration::get('mail', 'errors_go_to'));
    }

    /**
     *  \brief Add a standard email message header.
     */
    public function addHeader($header, $value)
    {
        $this->mailObj->addCustomHeader($header, $value);
    }

    /**
     *  \brief Add an address to 'To' field.
     *
     *  \param $email - the email address
     *  \param $name - the name of the person (optional)
     */
    public function addTo($email, $name = '')
    {
        $this->mailObj->addAddress($email, $name);
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
        $this->mailObj->addAttachment($path, $name, $encoding, $type);
    }

    /**
     *  \brief Add a category to the e-mail.
     *
     *  \param $category - the category
     */
    public function addCategory($category)
    {
        throw new \Exception('Resourse unavailable.');
    }

    /**
     *  \brief Set the mail subject.
     *
     *  \param $subject - the subject text
     */
    public function setSubject($subject)
    {
        $this->mailObj->Subject = $subject;
    }

    /**
     *  \brief Set the 'From' field.
     *
     *  \param $email - the email address
     *  \param $name - the name of the person (optional)
     */
    public function setFrom($email, $name = '')
    {
        $this->mailObj->setFrom($email, $name);
    }

    /**
     *  \brief Set the message bo.
     *
     *  \param $body - HTML ou text message body
     *  \param $html - set true if body is HTML ou false if plain text
     */
    public function setBody($body, $html = true)
    {
        $this->mailObj->isHTML($html);
        $this->mailObj->Body = $body;
    }

    /**
     *	\brief Set the alternative plain-text message body for old message readers.
     */
    public function setAlternativeBody($text)
    {
        $this->mailObj->AltBody = $text;
    }

    /**
     *  \brief Set a template for this email.
     */
    public function setTemplate($name)
    {
        throw new \Exception('Resourse unavailable.');
    }

    /**
     *  \brief Add value to a template variable.
     */
    public function addTemplateVar($name, $value)
    {
        throw new \Exception('Resourse unavailable.');
    }

    /**
     *  \brief Send the mail message
     *  \return The error message or a empty string if success.
     */
    public function send()
    {
        $error = false;

        if (!$this->mailObj->send()) {
            $error = $this->mailObj->ErrorInfo;
        }

        return $error;
    }
}
