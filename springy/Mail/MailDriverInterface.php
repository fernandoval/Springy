<?php
/**	\file
 *	Springy.
 *
 *	\brief      Interface for mail drivers.
 *  \copyright  ₢ 2007-2016 Fernando Val
 *  \author     Fernando Val - fernando.val@gmail.com
 *	\version    2.1.0.4
 *	\ingroup    framework
 */

namespace Springy\Mail;

/**
 *  \brief Interface for mail drivers.
 *
 *  \note This class is a interface for construction of mail drivers.
 */
interface MailDriverInterface
{
    /**
     *  \brief Add a standard email message header.
     */
    public function addHeader($header, $value);

    /**
     *  \brief Add an address to 'To' field.
     *
     *  \param $email - the email address
     *  \param $name - the name of the person (optional)
     */
    public function addTo($email, $name = '');

    /**
     *  \brief Add an address to 'BCC' field.
     *
     *  \param $email - the email address
     *  \param $name - the name of the person (optional)
     */
    public function addBCC($email, $name = '');

    /**
     *  \brief Add an address to 'CC' field.
     *
     *  \param $email - the email address
     *  \param $name - the name of the person (optional)
     */
    public function addCC($email, $name = '');

    /**
     *  \brief Add a file to be attached to the e-mail.
     *
     *  \param $path - full pathname to the attachment
     *  \param $name - override the attachment name (optional)
     *  \param $type - MIME type/file extension type (optional)
     *  \param $encoding - file enconding (optional)
     */
    public function addAttachment($path, $name = '', $type = '', $encoding = 'base64');

    /**
     *  \brief Add a category to the e-mail.
     *
     *  \param $category - the category
     */
    public function addCategory($category);

    /**
     *  \brief Set the 'From' field.
     *
     *  \param $email - the email address
     *  \param $name - the name of the person (optional)
     */
    public function setFrom($email, $name = '');

    /**
     *  \brief Set the mail subject.
     *
     *  \param $subject - the subject text
     */
    public function setSubject($subject);

    /**
     *  \brief Set the message bo.
     *
     *  \param $body - HTML ou text message body
     *  \param $html - set true if body is HTML ou false if plain text
     */
    public function setBody($body, $html = true);

    /**
     *	\brief Set the alternative plain-text message body for old message readers.
     */
    public function setAlternativeBody($text);

    /**
     *  \brief Set a template for this email.
     */
    public function setTemplate($name);

    /**
     *  \brief Add value to a template variable.
     */
    public function addTemplateVar($name, $value);

    /**
     *  \brief Send the mail message
     *  \return The error message or a empty string if success.
     */
    public function send();
}
