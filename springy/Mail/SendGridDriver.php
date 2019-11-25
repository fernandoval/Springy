<?php
/**
 * Driver class for use with SendGrid v7 class for integration with SendGrid API v3.
 *
 * @copyright 2015 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 *
 * @see       https://github.com/sendgrid/sendgrid-php
 *
 * @version    3.2.0.9
 */

namespace Springy\Mail;

use Springy\Configuration;

/**
 * Driver class for use with SendGrid v7 class for integration with SendGrid API v3.
 *
 * Note: This classe is a driver used by Springy\Mail classe.
 *       Do not use it directly.
 */
class SendGridDriver implements MailDriverInterface
{
    private $sendgrid = null;
    private $mailObj = null;

    /**
     * Constructor method.
     *
     * @param array $cfg configurations array.
     */
    public function __construct($cfg)
    {
        if (!isset($cfg['apikey']) || empty($cfg['apikey'])) {
            throw new \Exception('Mail configuration SendGrid authentication undefined');
        }

        $options = (isset($cfg['options']) && is_array($cfg['options'])) ? $cfg['options'] : [];

        $this->sendgrid = new \SendGrid($cfg['apikey'], $options);
        $this->mailObj = new \SendGrid\Mail\Mail();

        if (Configuration::get('mail', 'errors_go_to')) {
            $this->mailObj->addHeader('Errors-To', Configuration::get('mail', 'errors_go_to'));
        }
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
        $this->mailObj->personalization[0]->addHeader($header, $value);
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
        $this->mailObj->addTo($email, $name);
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
        // $fileEncoded = base64_encode(file_get_contents($path));
        // Removed base64_encode due to bug in SDK
        $fileEncoded = file_get_contents($path);
        $this->mailObj->addAttachment($fileEncoded, $type, $name);
    }

    /**
     * Adds a category to the e-mail.
     *
     * @param string $category the category.
     *
     * @return void
     */
    public function addCategory($category)
    {
        $this->mailObj->addCategory($category);
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
        $this->mailObj->setSubject($subject);
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
        if ($html) {
            $this->mailObj->addContent('text/html', $body);

            return;
        }

        $this->mailObj->addContent('text/plain', $body);
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
        $this->mailObj->addContent('text/plain', $text);
    }

    /**
     * Sets a transactional template for this email.
     *
     * @param string $name the id of the template.
     *
     * @return void
     */
    public function setTemplate($name)
    {
        $this->mailObj->setTemplateId($name);
    }

    /**
     * Adds value to a template variable.
     *
     * @param string $name  name of the template variable.
     * @param string $value the value.
     *
     * @return void
     */
    public function addTemplateVar($name, $value)
    {
        $this->mailObj->addSubstitution($name, $value);
    }

    /**
     * Sends the mail message.
     *
     * @return string The error message or a empty string if success.
     */
    public function send()
    {
        $error = false;

        try {
            $response = $this->sendgrid->send($this->mailObj);
            $error = $response->body();
        } catch (\Exception $err) {
            $error = $err->getCode() . ' - ' . $err->getMessage() . ' at ' . $err->getFile() . ' (' . $err->getLine() . ')';
        }

        return $error;
    }
}
