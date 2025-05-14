<?php

/**
 * Interface for mail drivers implementations.
 *
 * @copyright 2025 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 * @license   https://github.com/springy-framework/core/blob/master/LICENSE MIT
 */

namespace Springy\Mail\Drivers;

interface MailDriverInterface
{
    /**
     * Adds a file to be attached to the e-mail.
     *
     * @param string $path full pathname to the attachment.
     * @param string $name override the attachment name.
     * @param string $type MIME type/file extension type.
     */
    public function addAttachment(string $path, string $name = '', string $type = ''): void;

    /**
     * Adds an address to the 'BCC' field.
     *
     * @param string $email the email address.
     * @param string $name  the name of the person (optional).
     */
    public function addBcc(string $email, string $name = ''): void;

    /**
     * Adds a category to the e-mail.
     */
    public function addCategory(string $category): void;

    /**
     * Adds an address to the 'CC' field.
     *
     * @param string $email the email address.
     * @param string $name  the name of the person (optional).
     */
    public function addCc(string $email, string $name = ''): void;

    /**
     * Adds a standard email message header.
     *
     * @param string $header
     * @param string $value
     */
    public function addHeader(string $header, string $value): void;

    /**
     * Adds value to a template variable.
     *
     * @param string $name  name of the template variable.
     * @param string $value the value.
     */
    public function addTemplateVar(string $name, string $value): void;

    /**
     * Adds an address to the 'To' field.
     *
     * @param string $email the email address.
     * @param string $name  the name of the person (optional)
     */
    public function addTo(string $email, string $name = ''): void;

    /**
     * Gets the last send error message.
     *
     * @return string
     */
    public function getLastError(): string;

    /**
     * Sends the e-mail message.
     */
    public function send(): void;

    /**
     * Sets the alternative plain-text message body for old message readers.
     *
     * @param string $text
     */
    public function setAlternativeBody(string $text): void;

    /**
     * Adds message content body.
     *
     * @param string $body HTML ou text message body.
     * @param bool   $html set true if body is HTML ou false if plain text.
     */
    public function setBody(string $body, bool $html = true): void;

    /**
     * Sets the 'From' field.
     *
     * @param string $email the email address.
     * @param string $name  the name of the person (optional).
     */
    public function setFrom(string $email, string $name = ''): void;

    /**
     * Sets the mail subject.
     *
     * @param string $subject the subject text.
     */
    public function setSubject(string $subject): void;

    /**
     * Sets a transactional template for this email.
     *
     * @param string $tid the id of the template.
     */
    public function setTemplateId(string $tid): void;
}
