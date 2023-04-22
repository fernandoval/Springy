<?php

/**
 * Driver for use with PHPMaier class.
 *
 * @copyright 2016 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 *
 * @see       https://github.com/PHPMailer/PHPMailer
 *
 * @version    1.2.0
 */

namespace Springy\Mail;

use PHPMailer\PHPMailer\PHPMailer;
use Springy\Configuration;
use Springy\Kernel;

/**
 * Driver class for use with PHPMaier.
 *
 * Note: This classe is a driver used by Springy\Mail classe.
 *       Do not use it directly.
 */
class PHPMailerDriver implements MailDriverInterface
{
    private $mailObj = null;

    /**
     * Constructor method.
     *
     * @param array $cfg configurations array.
     */
    public function __construct($cfg)
    {
        if (!isset($cfg['protocol'])) {
            throw new \Exception('Mail configuration \'protocol\' undefined');
        }

        $this->mailObj = new PHPMailer();
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
            $this->mailObj->Sender = Configuration::get('mail', 'errors_go_to');
        }

        $this->mailObj->addCustomHeader('Errors-To', Configuration::get('mail', 'errors_go_to'));
    }

    /**
     * Adds a standard email message header.
     *
     * @param string $header
     * @param string $value
     *
     * @return void
     */
    public function addHeader($header, $value)
    {
        $this->mailObj->addCustomHeader($header, $value);
    }

    /**
     * Adds an address to 'To' field.
     *
     * @param string $email the email address.
     * @param string $name  the name of the person (optional)
     *
     * @return void
     */
    public function addTo($email, $name = '')
    {
        $this->mailObj->addAddress($email, $name);
    }

    /**
     * Adds an address to 'BCC' field.
     *
     * @param string $email the email address.
     * @param string $name  the name of the person (optional).
     *
     * @return void
     */
    public function addBCC($email, $name = '')
    {
        $this->mailObj->addBcc($email, $name);
    }

    /**
     * Adds an address to 'CC' field.
     *
     * @param string $email the email address.
     * @param string $name  the name of the person (optional).
     *
     * @return void
     */
    public function addCC($email, $name = '')
    {
        $this->mailObj->addCc($email, $name);
    }

    /**
     * Adds a file to be attached to the e-mail.
     *
     * @param string $path     full pathname to the attachment.
     * @param string $name     override the attachment name (optional).
     * @param string $type     MIME type/file extension type (optional).
     * @param string $encoding file enconding (unused).
     *
     * @return void
     */
    public function addAttachment($path, $name = '', $type = '', $encoding = 'base64')
    {
        $this->mailObj->addAttachment($path, $name, $encoding, $type);
    }

    /**
     * Throws Exception because this method is not supported by PHPMailer.
     *
     * @param string $category the category.
     *
     * @return void
     *
     * @throws Exception
     */
    public function addCategory($category)
    {
        throw new \Exception('Resourse unavailable.');
    }

    /**
     * Sets the 'From' field.
     *
     * @param string $email the email address.
     * @param string $name  the name of the person (optional).
     *
     * @return void
     */
    public function setFrom($email, $name = '')
    {
        $this->mailObj->setFrom($email, $name);
    }

    /**
     * Sets the mail subject.
     *
     * @param string $subject the subject text.
     *
     * @return void
     */
    public function setSubject($subject)
    {
        $this->mailObj->Subject = $subject;
    }

    /**
     * Adds message content body.
     *
     * @param string $body HTML ou text message body.
     * @param bool   $html set true if body is HTML ou false if plain text.
     *
     * @return void
     */
    public function setBody($body, $html = true)
    {
        $this->mailObj->isHTML($html);
        $this->mailObj->Body = $body;
    }

    /**
     * Sets the alternative plain-text message body for old message readers.
     *
     * @param string $text
     *
     * @return void
     */
    public function setAlternativeBody($text)
    {
        $this->mailObj->AltBody = $text;
    }

    /**
     * Throws Exception because this method is not supported by PHPMailer.
     *
     * @param string $name the id of the template.
     *
     * @return void
     *
     * @throws Exception
     */
    public function setTemplate($name)
    {
        throw new \Exception('Resourse unavailable.');
    }

    /**
     * Throws Exception because this method is not supported by PHPMailer.
     *
     * @param string $name  name of the template variable.
     * @param string $value the value.
     *
     * @return void
     *
     * @throws Exception
     */
    public function addTemplateVar($name, $value)
    {
        throw new \Exception('Resourse unavailable.');
    }

    /**
     * Sends the mail message.
     *
     * @return string The error message or a empty string if success.
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
