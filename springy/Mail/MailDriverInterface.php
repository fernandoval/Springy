<?php

/**
 * Interface for mail drivers.
 *
 * @copyright 2007 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 * @license   https://github.com/fernandoval/Springy/blob/master/LICENSE MIT
 *
 * @version   2.1.5
 */

namespace Springy\Mail;

/**
 * Interface for mail drivers.
 *
 * This class is a interface for construction of mail drivers.
 */
interface MailDriverInterface
{
    /**
     * Add a standard email message header.
     */
    public function addHeader($header, $value);

    /**
     * Add an address to 'To' field.
     *
     * @param string $email the email address
     * @param string $name  the name of the person (optional)
     */
    public function addTo($email, $name = '');

    /**
     * Add an address to 'BCC' field.
     *
     * @param string $email the email address
     * @param string $name  the name of the person (optional)
     */
    public function addBCC($email, $name = '');

    /**
     * Add an address to 'CC' field.
     *
     * @param string $email the email address
     * @param string $name  the name of the person (optional)
     */
    public function addCC($email, $name = '');

    /**
     * Add a file to be attached to the e-mail.
     *
     * @param string $path     full pathname to the attachment
     * @param string $name     override the attachment name (optional)
     * @param string $type     MIME type/file extension type (optional)
     * @param string $encoding file enconding (optional)
     */
    public function addAttachment($path, $name = '', $type = '', $encoding = 'base64');

    /**
     * Add a category to the e-mail.
     *
     * @param string $category the category
     */
    public function addCategory($category);

    /**
     * Set the 'From' field.
     *
     * @param string $email the email address
     * @param string $name  the name of the person (optional)
     */
    public function setFrom($email, $name = '');

    /**
     * Set the mail subject.
     *
     * @param string $subject the subject text
     */
    public function setSubject($subject);

    /**
     * Set the message bo.
     *
     * @param string $body HTML ou text message body
     * @param string $html set true if body is HTML ou false if plain text
     */
    public function setBody($body, $html = true);

    /**
     * Set the alternative plain-text message body for old message readers.
     */
    public function setAlternativeBody($text);

    /**
     * Set a template for this email.
     */
    public function setTemplate($name);

    /**
     * Add value to a template variable.
     */
    public function addTemplateVar($name, $value);

    /**
     * Send the mail message
     *
     * @return mixed The error message or a empty string if success.
     */
    public function send();
}
