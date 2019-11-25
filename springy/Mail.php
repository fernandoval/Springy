<?php
/**	\file
 *	Springy.
 *
 *	\brief      Classe para envio de email.
 *  \copyright  ₢ 2007-2016 Fernando Val
 *  \author     Fernando Val - fernando.val@gmail.com
 *	\version    3.1.0.21
 *	\ingroup    framework
 */

namespace Springy;

/**
 *  \brief Classe para envio de email.
 */
class Mail
{
    const MAIL_ENGINE_PHPMAILER = 'phpmailer';
    const MAIL_ENGINE_SWIFTMAILER = 'swiftmailer';
    const MAIL_ENGINE_SENDGRID = 'sendgrid';
    const MAIL_ENGINE_MIMEMESSAGE = 'mimemessage';

    private $mailObj = null;

    /**
     *	\brief Construtor da classe.
     */
    public function __construct($mailer = null)
    {
        if (is_null($mailer)) {
            if (Configuration::get('mail', 'default_driver')) {
                $mailer = Configuration::get('mail', 'default_driver');
            } else {
                $mailer = key(Configuration::get('mail', 'mailers'));
            }
        }
        $cfg = Configuration::get('mail', 'mailers.' . $mailer);

        if ($cfg == null) {
            throw new \Exception('Mail configuration \'mailers.' . $mailer . '\' undefined');
        }
        if (!isset($cfg['driver'])) {
            throw new \Exception('Mail configuration \'driver\' undefined');
        }

        // Inicializa a classe de template
        switch (strtolower($cfg['driver'])) {
            case self::MAIL_ENGINE_PHPMAILER:
                $this->mailObj = new Mail\PHPMailerDriver($cfg);
                break;
            case self::MAIL_ENGINE_SWIFTMAILER:
                $this->mailObj = new Mail\SwiftMailerDriver($cfg);
                break;
            case self::MAIL_ENGINE_SENDGRID:
                $this->mailObj = new Mail\SendGridDriver($cfg);
                break;
            case self::MAIL_ENGINE_MIMEMESSAGE:
                $this->mailObj = new Mail\MimeMessageDriver($cfg);
                break;
            default:
                throw new \Exception('Mail driver invalid');
        }

        return true;
    }

    /**
     *  \brief Destrói o objeto.
     */
    public function __destruct()
    {
        unset($this->mailObj);
    }

    /**
     *  \brief Add a standard email message header.
     */
    public function addHeader($header, $value)
    {
        $this->mailObj->addHeader($header, $value);
    }

    /**
     *	\brief Define o valor de um item de cabeçalho.
     */
    public function setHeader($header, $value)
    {
        $this->addHeader($header, $value);
    }

    /**
     *  \brief Set a template for the email.
     */
    public function setTemplate($name)
    {
        $this->mailObj->setTemplate($name);
    }

    /**
     *  \brief Add value to a template variable.
     */
    public function addTemplateVar($name, $value)
    {
        $this->mailObj->addTemplateVar($name, $value);
    }

    /**
     *	\brief Define o valor do campo To.
     *
     *	@param[in] $email - string contendo o endereço de email do destinatário ou um array contendo a lista de destinatários, no seguinte formato:
     *		['email1@dominio.com' => 'Nome 1', 'email2@dominio.com' => 'Nome 2']
     *	@param[in] $name - string contendo o nome do destinatário
     *
     *	Obs.: Caso seja passado um array de emails para $email, o valor de $name será ignorado.
     */
    public function to($email, $name = '')
    {
        // Verifica se há a entrada forçando o envio de todos os emails para um destinatário específico
        if (Configuration::get('mail', 'mails_go_to')) {
            $email = Configuration::get('mail', 'mails_go_to');
            $name = '';
        }

        if (is_array($email)) {
            foreach ($email as $mail => $name) {
                $this->mailObj->addTo($mail, $name);
            }
        } else {
            $this->mailObj->addTo($email, $name);
        }

        return true;
    }

    /**
     *	\brief Define o valor do campo Cc.
     */
    public function cc($email, $name = '')
    {
        if (is_array($email)) {
            foreach ($email as $mail => $name) {
                $this->mailObj->addCC($mail, $name);
            }
        } else {
            $this->mailObj->addCC($email, $name);
        }

        return true;
    }

    /**
     *	\brief Define o valor do campo Bcc.
     */
    public function bcc($email, $name = '')
    {
        if (is_array($email)) {
            foreach ($email as $mail => $name) {
                $this->mailObj->addBCC($mail, $name);
            }
        } else {
            $this->mailObj->addBCC($email, $name);
        }

        return true;
    }

    /**
     *	\brief Define o valor do campo From.
     */
    public function from($email, $name = '')
    {
        $this->mailObj->setFrom($email, $name);

        return true;
    }

    /**
     *	\brief Define o valor do campo Subject.
     */
    public function subject($subject)
    {
        $this->mailObj->setSubject($subject);

        return true;
    }

    /**
     *	\brief Monta o corpo da mensagem.
     */
    public function body($html = '', $text = '')
    {
        if ($text) {
            $this->mailObj->setAlternativeBody($text);
        }
        if ($html) {
            $this->mailObj->setBody($html);
        }
    }

    /**
     *	\brief Adiciona um anexo ao e-mail.
     */
    public function addAttach($path, $name = '', $type = '', $encoding = 'base64')
    {
        if (is_array($path)) {
            $name = $path['name'];
            $type = $path['type'];
            $path = $path['tmp_name'];
        }

        $this->mailObj->addAttachment($path, $name, $type, $encoding);
    }

    /**
     *  \brief Add a category to the e-mail.
     *
     *  \param $category - the category
     */
    public function addCategory($category)
    {
        $this->mailObj->addCategory($category);
    }

    /**
     *	\brief Envia a mensagem.
     */
    public function send()
    {
        return $this->mailObj->send();
    }

    /**
     *	\brief Envia a mensagem.
     *
     *	@param[in] (string) $from - endereço de email do remetente da mensagem
     *	@param[in] (string) $from_name - nome do remetente da mensagem
     *	@param[in] (string) $mailto - endereço de email do destinatário da mensagem
     *	@param[in] (string) $to_name - nome do destinatário da mensagem
     *	@param[in] (string) $subject - assunto da mensagem
     *	@param[in] (string) $htmlmessage - mensagem em formato HTML
     *	@param[in] (string) $textmessage - mensagem em formato texto puro
     *
     *	@return Retorna true se a mensagem foi enviada com sucesso ou a mensagem de erro
     */
    public function sendMessage($from, $from_name, $mailto, $to_name, $subject, $htmlmessage, $textmessage)
    {
        $this->from($from, $from_name);
        $this->to($mailto, $to_name);
        $this->subject($subject);
        $this->body($htmlmessage, $textmessage);

        return $this->send();
    }
}
