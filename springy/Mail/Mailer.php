<?php

/**
 * Mailer.
 *
 * @copyright 2025 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 * @author    Lucas Cardozo <lucas.cardozo@gmail.com>
 * @license   https://github.com/springy-framework/core/blob/master/LICENSE MIT
 */

namespace Springy\Mail;

use Springy\Exceptions\SpringyException;
use Springy\Mail\Drivers\MailDriverInterface;

class Mailer
{
    protected array $attachments;
    protected array $bcc;
    protected string $bodyHtml;
    protected string $bodyPlain;
    protected array $categories;
    protected array $headers;
    protected array $tos;
    protected array $vars;
    protected array $vcc;
    protected string $fakeTo;
    protected string $lastError;

    /** @var object the mailer driver object */
    protected $mailObj;

    public function __construct(
        protected string $fromEmail = '',
        protected string $fromName = '',
        protected string $subject = '',
        protected string $templateId = ''
    ) {
        $this->attachments = [];
        $this->bcc = [];
        $this->bodyHtml = '';
        $this->bodyPlain = '';
        $this->categories = [];
        $this->headers = [];
        $this->tos = [];
        $this->vars = [];
        $this->vcc = [];
        $this->fakeTo = config_get('mail.fake_to', '');
        $this->lastError = '';

        $driverClass = config_get('mail.driver');

        if (!is_string($driverClass)) {
            throw new SpringyException('Invalid mail driver definition');
        } elseif (!class_exists($driverClass)) {
            throw new SpringyException('Mail driver class not found');
        } elseif (is_null(config_get('mail.settings'))) {
            throw new SpringyException('Mail driver configuration settings not defined');
        }

        $errorsTo = config_get('mail.errors_go_to', '');

        if ($errorsTo !== '') {
            $this->addHeader('Errors-To', $errorsTo);
        }
    }

    /**
     * Adds a file to be attached to the e-mail.
     *
     * @param string $path full pathname to the attachment.
     * @param string $name override the attachment name.
     * @param string $type MIME type/file extension type.
     */
    public function addAttachment(string $path, string $name = '', string $type = ''): self
    {
        $this->attachments[] = [
            'path' => $path,
            'name' => $name,
            'type' => $type,
        ];

        return $this;
    }

    /**
     * Adds an address to the 'BCC' field.
     *
     * @param string $email the email address.
     * @param string $name  the name of the person (optional).
     */
    public function addBcc(string $email, string $name = ''): self
    {
        if (!$this->fakeTo) {
            $this->bcc[] = [
                'email' => $email,
                'name' => $name,
            ];
        }

        return $this;
    }

    /**
     * Adds a category to the e-mail.
     */
    public function addCategory(string $category): self
    {
        $this->categories[] = $category;

        return $this;
    }

    /**
     * Adds an address to the 'CC' field.
     *
     * @param string $email the email address.
     * @param string $name  the name of the person (optional).
     */
    public function addCc(string $email, string $name = ''): self
    {
        if (!$this->fakeTo) {
            $this->vcc[] = [
                'email' => $email,
                'name' => $name,
            ];
        }

        return $this;
    }

    /**
     * Adds an email message header.
     */
    public function addHeader(string $header, string $value): self
    {
        $this->headers[] = [
            'key' => $header,
            'val' => $value,
        ];

        return $this;
    }

    /**
     * Adds value to a template variable.
     *
     * @param string $name  name of the template variable.
     * @param string $value the value.
     */
    public function addTemplateVar(string $name, string $value): self
    {
        $this->vars[] = [
            'name' => $name,
            'val' => $value,
        ];

        return $this;
    }

    /**
     * Adds an address to the 'To' field.
     *
     * @param string $email the email address.
     * @param string $name  the name of the person (optional)
     */
    public function addTo(string $email, string $name = ''): self
    {
        if (!$this->fakeTo) {
            $this->tos[] = [
                'email' => $email,
                'name' => $name,
            ];
        }

        return $this;
    }

    /**
     * Build the mailer object driver.
     *
     * @throws SpringyException
     */
    private function createDriver(): MailDriverInterface
    {
        $driver = new (config_get('mail.driver'))(config_get('mail.settings'));

        if (!$driver instanceof MailDriverInterface) {
            throw new SpringyException('Invalid mail driver definition');
        }

        return $driver;
    }

    /**
     * Gets the last send error message.
     */
    public function getLastError(): string
    {
        return $this->lastError;
    }

    /**
     * Sends the mail message.
     */
    public function send(): self
    {
        $driver = $this->createDriver();
        $driver->setFrom($this->fromEmail, $this->fromName);
        $driver->setSubject($this->subject);
        $driver->setBody($this->bodyHtml !== '' ? $this->bodyHtml : $this->bodyPlain, $this->bodyHtml !== '');

        if ($this->bodyHtml !== '') {
            $driver->setAlternativeBody($this->bodyPlain);
        }

        array_walk($this->headers, fn (array $header) => $driver->addHeader($header['key'], $header['val']));
        $this->fakeTo
            ? $this->mailObj->addTo($this->fakeTo)
            : array_walk($this->tos, fn (array $toa) => $driver->addTo($toa['email'], $toa['name']));
        array_walk($this->bcc, fn (array $bcc) => $driver->addBcc($bcc['email'], $bcc['name']));
        array_walk($this->vcc, fn (array $vcc) => $driver->addCc($vcc['email'], $vcc['name']));
        array_walk($this->categories, fn (string $category) => $driver->addCategory($category));
        array_walk(
            $this->attachments,
            fn (array $attachment) => $driver->addAttachment(
                $attachment['path'],
                $attachment['name'],
                $attachment['type']
            )
        );

        if ($this->templateId !== '') {
            $driver->setTemplateId($this->templateId);
            array_walk($this->vars, fn (string $var) => $driver->addTemplateVar($var['name'], $var['val']));
        }

        $driver->send();
        $this->lastError = $driver->getLastError();

        return $this;
    }

    /**
     * Sets the alternative plain-text message body for old message readers.
     */
    public function setAlternativeBody(string $text): self
    {
        $this->bodyPlain = $text;

        return $this;
    }

    /**
     * Adds message content body.
     *
     * @param string $body HTML ou text message body.
     * @param bool   $html set true if body is HTML ou false if plain text.
     */
    public function setBody(string $body, bool $html): self
    {
        $html
            ? $this->bodyHtml = $body
            : $this->bodyPlain = $body;

        return $this;
    }

    /**
     * Sets the 'From' field.
     *
     * @param string $email the email address.
     * @param string $name  the name of the person (optional).
     */
    public function setFrom(string $email, string $name = ''): self
    {
        $this->fromEmail = $email;
        $this->fromName = $name;

        return $this;
    }

    /**
     * Sets the mail subject.
     *
     * @param string $subject the subject text.
     */
    public function setSubject(string $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * Sets a mailing engine template for this email.
     *
     * @param string $tid the id of the template.
     */
    public function setTemplateId($tid): self
    {
        $this->templateId = $tid;

        return $this;
    }
}
