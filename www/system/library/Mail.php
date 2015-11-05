<?php
/**	\file
 *	FVAL PHP Framework for Web Applications
 *
 *  \copyright  Copyright (c) 2007-2015 FVAL Consultoria e Informática Ltda.\n
 *  \copyright  Copyright (c) 2007-2015 Fernando Val\n
 *	\copyright  Copyright (c) 2009-2013 Lucas Cardozo
 *
 *	\brief      Classe para envio de email
 *	\warning    Este arquivo é parte integrante do framework e não pode ser omitido
 *	\version    1.9.14
 *  \author     Fernando Val  - fernando.val@gmail.com
 *  \author     Lucas Cardozo - lucas.cardozo@gmail.com
 *	\ingroup    framework
 */

namespace FW;

/**
 *  \brief Classe para envio de email
 */
class Mail
{
	private $message_obj = NULL;
	private $html_part = NULL;
	private $text_part = NULL;
	private $html_message = '';
	private $text_message = '';
	private $mail_subject = '';
	private $mail_to = '';
	private $mail_name = '';

	/**
	 *	\brief Construtor da classe
	 */
	function __construct()
	{
		if (Configuration::get('mail', 'method') == 'smtp') {
			require_once Kernel::path(Kernel::PATH_VENDOR) . DIRECTORY_SEPARATOR . 'MimeMessage' . DIRECTORY_SEPARATOR . 'email_message.php';
			require_once Kernel::path(Kernel::PATH_VENDOR) . DIRECTORY_SEPARATOR . 'MimeMessage' . DIRECTORY_SEPARATOR . 'smtp_message.php';
			require_once Kernel::path(Kernel::PATH_VENDOR) . DIRECTORY_SEPARATOR . 'Smtp' . DIRECTORY_SEPARATOR . 'smtp.php';

			if (Configuration::get('mail', 'ssl') || Configuration::get('mail', 'starttls')) {
				require_once Kernel::path(Kernel::PATH_VENDOR) . DIRECTORY_SEPARATOR . 'Sasl' . DIRECTORY_SEPARATOR . 'sasl.php';
			}

			$this->message_obj = new \smtp_message_class;

			$this->message_obj->localhost = Configuration::get('mail', 'workstation');
			$this->message_obj->smtp_host = Configuration::get('mail', 'host');
			$this->message_obj->smtp_port = Configuration::get('mail', 'port');
			$this->message_obj->smtp_ssl = Configuration::get('mail', 'ssl');
			$this->message_obj->smtp_start_tls = Configuration::get('mail', 'starttls');
			$this->message_obj->smtp_direct_delivery = Configuration::get('mail', 'direct_delivery');
			$this->message_obj->smtp_exclude_address = Configuration::get('mail', 'exclude_address');
			$this->message_obj->smtp_user = Configuration::get('mail', 'user');
			$this->message_obj->smtp_realm = Configuration::get('mail', 'realm');
			$this->message_obj->smtp_workstation = Configuration::get('mail', 'workstation');
			$this->message_obj->smtp_password = Configuration::get('mail', 'pass');
			$this->message_obj->smtp_pop3_auth_host = Configuration::get('mail', 'auth_host');
			$this->message_obj->smtp_debug = Configuration::get('mail', 'debug');
			$this->message_obj->smtp_html_debug = Configuration::get('mail', 'html_debug');

			if (Configuration::get('system', 'proxyhost')) {
				$this->message_obj->smtp_http_proxy_host_name = Configuration::get('system', 'proxyhost');
				$this->message_obj->smtp_http_proxy_host_port = Configuration::get('system', 'proxyport');
				//$this->message_obj-> = Configuration::get('system', 'proxyusername');
				//$this->message_obj-> = Configuration::get('system', 'proxypassword');
			}
		}
		elseif (Configuration::get('mail', 'method') == 'sendmail') {
			require_once Kernel::path(Kernel::PATH_VENDOR) . DIRECTORY_SEPARATOR . 'MimeMessage' . DIRECTORY_SEPARATOR . 'email_message.php';
			require_once Kernel::path(Kernel::PATH_VENDOR) . DIRECTORY_SEPARATOR . 'MimeMessage' . DIRECTORY_SEPARATOR . 'sendmail_message.php';

			$this->message_obj = new \sendmail_message_class;

			$this->message_obj->delivery_mode = \SENDMAIL_DELIVERY_DEFAULT;
			$this->message_obj->bulk_mail_delivery_mode = \SENDMAIL_DELIVERY_QUEUE;
			$this->message_obj->sendmail_arguments = '';
		}
		elseif (Configuration::get('mail', 'method') == 'sendgrid') {
			$this->message_obj = new \SendGrid\Email();
		} else {
			require_once Kernel::path(Kernel::PATH_VENDOR) . DIRECTORY_SEPARATOR . 'MimeMessage' . DIRECTORY_SEPARATOR . 'email_message.php';
			require_once Kernel::path(Kernel::PATH_VENDOR) . DIRECTORY_SEPARATOR . 'MimeMessage' . DIRECTORY_SEPARATOR . 'sendmail_message.php';
			
			$this->message_obj = new \email_message_class;

			$this->message_obj->localhost = Configuration::get('mail', 'workstation');
		}

		$this->setEmailHeader('Errors-To', Configuration::get('mail', 'errors_go_to'), Configuration::get('mail', 'errors_go_to'));
	}

	/**
	 *	\brief Define o valor de um item de cabeçalho
	 */
	public function setHeader($header, $value)
	{
		if (Configuration::get('mail', 'method') != 'sendgrid') {
			$this->message_obj->SetEncodedHeader($header, $value, Kernel::charset());
		} else {
			if ($header == 'Subject') {
				$this->message_obj->setSubject($value);
			}
			elseif ($header == 'ReplyTo') {
				$this->message_obj->setReplyTo($value);
			}
		}
	}

	/**
	 *	\brief Define o valor de um item de cabeçalho
	 */
	public function setEmailHeader($header, $email, $name='')
	{
		if (Configuration::get('mail', 'method') == 'sendgrid') {
			if ($header == 'To') {
				if (is_array($email)) {
					foreach($email as $addr => $name) {
						$this->message_obj->addTo($addr, $name);
					}
				} else {
					$this->message_obj->addTo($email, $name);
				}
			}
			elseif ($header == 'Cc') {
				if (is_array($email)) {
					foreach($email as $addr => $name) {
						$this->message_obj->addCc($addr, $name);
					}
				} else {
					$this->message_obj->addCc($email, $name);
				}
			}
			elseif ($header == 'Bcc') {
				if (is_array($email)) {
					foreach($email as $addr => $name) {
						$this->message_obj->addBcc($addr, $name);
					}
				} else {
					$this->message_obj->addBcc($email, $name);
				}
			}
			elseif ($header == 'From') {
				$this->message_obj->setFrom($email);
				$this->message_obj->setFromName($name);
			}
		}
		elseif (is_array($email)) {
			$this->message_obj->SetMultipleEncodedEmailHeader($header, $email, Kernel::charset());
		} else {
			$this->message_obj->SetEncodedEmailHeader($header, $email, $name, Kernel::charset());
		}
	}

	/**
	 *	\brief Define o valor do campo To
	 *
	 *	@param[in] $email - string contendo o endereço de email do destinatário ou um array contendo a lista de destinatários, no seguinte formato:
	 *		['email1@dominio.com' => 'Nome 1', 'email2@dominio.com' => 'Nome 2']
	 *	@param[in] $name - string contendo o nome do destinatário
	 *
	 *	Obs.: Caso seja passado um array de emails para $email, o valor de $name será ignorado.
	 */
	public function to($email, $name='')
	{
		// Verifica se há a entrada forçando o envio de todos os emails para um destinatário específico
		if (Configuration::get('mail', 'mails_go_to')) {
			$email = Configuration::get('mail', 'mails_go_to');
			$name = '';
		}

		$this->setEmailHeader('To', $email, $name);

		if (is_array($email)) {
			reset($email);
			$this->mail_to = key($email);
		} else {
			$this->mail_to = $email;
		}
	}

	/**
	 *	\brief Define o valor do campo Cc
	 */
	public function cc($email, $name='')
	{
		$this->setEmailHeader('Cc', $email, $name);
	}

	/**
	 *	\brief Define o valor do campo Bcc
	 */
	public function bcc($email, $name='')
	{
		$this->setEmailHeader('Bcc', $email, $name);
	}

	/**
	 *	\brief Define o valor do campo From
	 */
	public function from($email, $name='')
	{
		$this->setEmailHeader('From', $email, $name);
		$this->setHeader('Sender', $email, $name);
	}

	/**
	 *	\brief Define o valor do campo Subject
	 */
	public function subject($subject)
	{
		$this->setHeader('Subject', $subject);
		$this->mail_subject = $subject;
	}

	/**
	 *	\brief Monta o corpo da mensagem
	 */
	public function body($html='', $text='')
	{
		$alternative_parts = array();
		// [pt-br] Monta a parte TXT
		if ($text) {
			$this->text_message = $text;
			if (Configuration::get('mail', 'method') == 'sendgrid') {
				$this->message_obj->setText($text);
			} else {
				$this->message_obj->CreateQuotedPrintableTextPart($text, Kernel::charset(), $this->text_part);
				$alternative_parts[] = $this->text_part;
			}
		}
		// [pt-br] Monta a parte HTML
		if ($html) {
			$this->html_message = $html;
			if (Configuration::get('mail', 'method') == 'sendgrid') {
				$this->message_obj->setHtml($html);
			} else {
				$this->message_obj->CreateQuotedPrintableHTMLPart($html, Kernel::charset(), $this->html_part);
				$alternative_parts[] = $this->html_part;
			}
		}
		// [pt-br] Monta as partes alternativas
		if (Configuration::get('mail', 'method') != 'sendgrid') {
			$this->message_obj->AddAlternativeMultipart($alternative_parts);
		}
	}

	/**
	 *	\brief Adiciona um anexo ao e-mail
	 */
	public function addAttach($attach)
	{
		$file['FileName'] = $attach['tmp_name'];
		$file['Name'] = $attach['name'];
		$file['Content-Type'] = $attach['type'];

		if (Configuration::get('mail', 'method') == 'sendgrid') {
			$this->message_obj->addAttachment($attach['tmp_name']);
		} else {
			$this->message_obj->AddFilePart($file);
		}

		return $file;
	}

	/**
	 *	\brief Envia a mensagem
	 */
	public function send()
	{
		if (Configuration::get('mail', 'method') == 'sendgrid') {
			$sendgrid = new \SendGrid(
				Configuration::get('mail', 'user'),
				Configuration::get('mail', 'pass')
			);
			
			try {
				$sendgrid->send($this->message_obj);
				$error = "";
			} catch(\SendGrid\Exception $e) {
				$error = $e->getCode() . "\n";
				foreach($e->getErrors() as $er) {
					$error .= $er . "\n";
				}
			}
		} elseif (Configuration::get('mail', 'method') == 'sendmail') {
			$error = $this->message_obj->Mail($this->mail_to, $this->mail_subject, $this->text_message, '', '');
		} else {
			$error = $this->message_obj->Send();
		}
		return $error;
	}

	/**
	 *	\brief Envia a mensagem
	 *
	 *	@param[in] (string) $from - endereço de email do remetente da mensagem
	 *	@param[in] (string) $from_name - nome do remetente da mensagem
	 *	@param[in] (string) $mailto - endereço de email do destinatário da mensagem
	 *	@param[in] (string) $to_name - nome do destinatário da mensagem
	 *	@param[in] (string) $subject - assunto da mensagem
	 *	@param[in] (string) $htmlmessage - mensagem em formato HTML
	 *	@param[in] (string) $textmessage - mensagem em formato texto puro
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