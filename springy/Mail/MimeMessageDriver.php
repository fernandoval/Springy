<?php
/** \file
 *  Springy.
 *
 *  \brief      Class driver for use with Manuel Lemos' MIME e-mail message class.
 *
 *  \copyright  ₢ 2007-2018 Fernando Val
 *  \author     Fernando Val - fernando.val@gmail.com
 *
 *  \see        http://www.phpclasses.org/package/9-PHP-PHP-mailer-to-compose-and-send-MIME-messages.html
 *  \version    1.1.0.6
 *  \ingroup    framework
 */

namespace Springy\Mail;

use Springy\Configuration;
use Springy\Kernel;

/**
 *  \brief Driver class for sent mail using MIME e-mail message class developed by Manuel Lemos.
 *
 *  \warning This class was not tested yet. Because MIME e-mail message classes are not into Packagist repository
 *    we can not install it via Composer. Then you will need download and install it by yourself and create an
 *    autoload to it. There are commented lines in __construct method with require_once for each file needed.
 *
 *  \note This classe is a driver used by Springy\Mail classe.
 *        Do not use it directly.
 */
class MimeMessageDriver implements MailDriverInterface
{
    private $mailObj = null;
    private $sendmail = false;
    private $textMessage = '';
    private $alternativeParts = [];
    private $headers = [
        'Sender'  => '',
        'Subject' => '',
    ];
    private $mailHeaders = [
        'From'   => '',
        'Sender' => '',
        'To'     => [],
        'Cc'     => [],
        'Bcc'    => [],
    ];

    /**
     *  \brief Constructor method
     *  \param $cfg - array with de configuration.
     */
    public function __construct($cfg)
    {
        if (!isset($cfg['protocol'])) {
            throw new \Exception('Mail configuration \'protocol\' undefined');
        }

        if ($cfg['protocol'] == 'smtp') {
            // require_once Kernel::path(Kernel::PATH_VENDOR) . DIRECTORY_SEPARATOR . 'MimeMessage' . DIRECTORY_SEPARATOR . 'email_message.php';
            // require_once Kernel::path(Kernel::PATH_VENDOR) . DIRECTORY_SEPARATOR . 'MimeMessage' . DIRECTORY_SEPARATOR . 'smtp_message.php';
            // require_once Kernel::path(Kernel::PATH_VENDOR) . DIRECTORY_SEPARATOR . 'Smtp' . DIRECTORY_SEPARATOR . 'smtp.php';
            // if (Configuration::get('mail', 'ssl') || Configuration::get('mail', 'starttls')) {
            //     require_once Kernel::path(Kernel::PATH_VENDOR) . DIRECTORY_SEPARATOR . 'Sasl' . DIRECTORY_SEPARATOR . 'sasl.php';
            // }
            $this->mailObj = new \smtp_message_class();

            $this->mailObj->smtp_host = isset($cfg['host']) ? $cfg['host'] : 'localhost';
            $this->mailObj->smtp_port = isset($cfg['port']) ? $cfg['port'] : 25;
            $this->mailObj->smtp_ssl = isset($cfg['ssl']) ? $cfg['ssl'] : false;
            $this->mailObj->smtp_start_tls = isset($cfg['starttls']) ? $cfg['starttls'] : false;
            $this->mailObj->smtp_direct_delivery = isset($cfg['direct_delivery']) ? $cfg['direct_delivery'] : false;
            $this->mailObj->smtp_exclude_address = isset($cfg['exclude_address']) ? $cfg['exclude_address'] : '';
            $this->mailObj->smtp_user = isset($cfg['username']) ? $cfg['username'] : '';
            $this->mailObj->smtp_password = isset($cfg['password']) ? $cfg['password'] : '';
            $this->mailObj->smtp_realm = isset($cfg['realm']) ? $cfg['realm'] : '';
            $this->mailObj->smtp_workstation = isset($cfg['workstation']) ? $cfg['workstation'] : '';
            $this->mailObj->smtp_pop3_auth_host = isset($cfg['auth_host']) ? $cfg['auth_host'] : null;
            $this->mailObj->smtp_debug = isset($cfg['debug']) ? $cfg['debug'] : 0;
            $this->mailObj->smtp_html_debug = isset($cfg['html_debug']) ? $cfg['html_debug'] : 0;

            if (isset($cfg['proxyhost']) && $cfg['proxyhost']) {
                $this->mailObj->smtp_http_proxy_host_name = $cfg['proxyhost'];
                $this->mailObj->smtp_http_proxy_host_port = $cfg['proxyport'];
            }
        } elseif ($cfg['protocol'] == 'sendmail') {
            // require_once Kernel::path(Kernel::PATH_VENDOR) . DIRECTORY_SEPARATOR . 'MimeMessage' . DIRECTORY_SEPARATOR . 'email_message.php';
            // require_once Kernel::path(Kernel::PATH_VENDOR) . DIRECTORY_SEPARATOR . 'MimeMessage' . DIRECTORY_SEPARATOR . 'sendmail_message.php';
            $this->mailObj = new \sendmail_message_class();

            $this->mailObj->delivery_mode = \SENDMAIL_DELIVERY_DEFAULT;
            $this->mailObj->bulk_mail_delivery_mode = \SENDMAIL_DELIVERY_QUEUE;
            $this->mailObj->sendmail_arguments = '';

            $this->sendmail = true;
        } else {
            // require_once Kernel::path(Kernel::PATH_VENDOR) . DIRECTORY_SEPARATOR . 'MimeMessage' . DIRECTORY_SEPARATOR . 'email_message.php';
            // require_once Kernel::path(Kernel::PATH_VENDOR) . DIRECTORY_SEPARATOR . 'MimeMessage' . DIRECTORY_SEPARATOR . 'sendmail_message.php';
            $this->mailObj = new \email_message_class();

            $this->mailObj->smtp_workstation = isset($cfg['workstation']) ? $cfg['workstation'] : '';
        }

        if (Configuration::get('mail', 'errors_go_to')) {
            $this->mailObj->SetEncodedEmailHeader('Errors-To', Configuration::get('mail', 'errors_go_to'), '');
        }
    }

    /**
     *  \brief Add a standard email message header.
     */
    public function addHeader($header, $value)
    {
        $this->mailObj->SetEncodedHeader($header, $value, Kernel::charset());
    }

    /**
     *  \brief Set the value of an header that is meant to represent the e-mail address.
     */
    public function setEmailHeader($header, $email, $name = '')
    {
        if (is_array($email)) {
            $this->mailObj->SetMultipleEncodedEmailHeader($header, $email, Kernel::charset());
        } else {
            $this->mailObj->SetEncodedEmailHeader($header, $email, $name, Kernel::charset());
        }
    }

    /**
     *  \brief Add an address to 'To' field.
     *
     *  \param $email - the email address
     *  \param $name - the name of the person (optional)
     */
    public function addTo($email, $name = '')
    {
        $this->mailHeaders['To'][$email] = $name;
    }

    /**
     *  \brief Add an address to 'BCC' field.
     *
     *  \param $email - the email address
     *  \param $name - the name of the person (optional)
     */
    public function addBCC($email, $name = '')
    {
        $this->mailHeaders['Bcc'][$email] = $name;
    }

    /**
     *  \brief Add an address to 'CC' field.
     *
     *  \param $email - the email address
     *  \param $name - the name of the person (optional)
     */
    public function addCC($email, $name = '')
    {
        $this->mailHeaders['Cc'][$email] = $name;
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
        $this->mailObj->AddFilePart([
            'FileName'     => $path,
            'Name'         => $name,
            'Content-Type' => $type,
        ]);
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
     *  \brief Set the 'From' field.
     *
     *  \param $email - the email address
     *  \param $name - the name of the person (optional)
     */
    public function setFrom($email, $name = '')
    {
        $this->mailHeaders['From'] = [$email, $name];
        $this->headers['Sender'] = [$email, $name];
    }

    /**
     *  \brief Set the mail subject.
     *
     *  \param $subject - the subject text
     */
    public function setSubject($subject)
    {
        $this->headers['Subject'] = $subject;
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
            $this->mailObj->CreateQuotedPrintableHTMLPart($html, Kernel::charset(), $htmlPart);
            $this->alternativeParts[] = $htmlPart;
        } else {
            $this->textMessage = $text;
            $this->mailObj->CreateQuotedPrintableTextPart($text, Kernel::charset(), $textPart);
            $this->alternativeParts[] = $textPart;
        }
    }

    /**
     *  \brief Set the alternative plain-text message body for old message readers.
     */
    public function setAlternativeBody($text)
    {
        $this->setBody($text, false);
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

        foreach ($this->mailHeaders as $header => $value) {
            if (!empty($value)) {
                $this->setEmailHeader($header, $value);
            }
        }
        foreach ($this->headers as $header => $value) {
            if (!empty($value)) {
                $this->addHeader($header, $value);
            }
        }

        if ($this->sendmail) {
            $error = $this->mailObj->Mail(key($this->mailHeaders['To']), $this->headers['Subject'], $this->textMessage, '', '');
        } else {
            $this->mailObj->AddAlternativeMultipart($this->alternativeParts);
            $error = $this->mailObj->Send();
        }

        return $error;
    }
}
