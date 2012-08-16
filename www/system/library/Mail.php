<?php
/**
 *	FVAL PHP Framework for Web Applications\n
 *	Copyright (c) 2007-2011 FVAL Consultoria e Informática Ltda.\n
 *	Copyright (c) 2007-2011 Fernando Val
 *
 *	\warning Este arquivo é parte integrante do framework e não pode ser omitido
 *
 *	\version 1.3.5
 *
 *	\brief Classe para envio de email
 */

require_once dirname( __FILE__) . DIRECTORY_SEPARATOR . 'MimeMessage' . DIRECTORY_SEPARATOR . 'email_message.php';

class Mail extends Kernel {
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
	function __construct() {
		if (parent::get_conf('mail', 'method') == 'smtp') {
			error_reporting(E_ALL^E_NOTICE);
			restore_error_handler();

			require_once dirname( __FILE__) . DIRECTORY_SEPARATOR . 'MimeMessage' . DIRECTORY_SEPARATOR . 'smtp_message.php';
			require_once dirname( __FILE__) . DIRECTORY_SEPARATOR . 'Smtp' . DIRECTORY_SEPARATOR . 'smtp.php';

			if (parent::get_conf('mail', 'ssl') || parent::get_conf('mail', 'starttls')) {
				require_once dirname( __FILE__) . DIRECTORY_SEPARATOR . 'Sasl' . DIRECTORY_SEPARATOR . 'sasl.php';
			}

			$this->message_obj = new smtp_message_class;

			$this->message_obj->localhost = parent::get_conf('mail', 'workstation');
			$this->message_obj->smtp_host = parent::get_conf('mail', 'host');
			$this->message_obj->smtp_port = parent::get_conf('mail', 'port');
			$this->message_obj->smtp_ssl = parent::get_conf('mail', 'ssl');
			$this->message_obj->smtp_start_tls = parent::get_conf('mail', 'starttls');
			$this->message_obj->smtp_direct_delivery = parent::get_conf('mail', 'direct_delivery');
			$this->message_obj->smtp_exclude_address = parent::get_conf('mail', 'exclude_address');
			$this->message_obj->smtp_user = parent::get_conf('mail', 'user');
			$this->message_obj->smtp_realm = parent::get_conf('mail', 'realm');
			$this->message_obj->smtp_workstation = parent::get_conf('mail', 'workstation');
			$this->message_obj->smtp_password = parent::get_conf('mail', 'pass');
			$this->message_obj->smtp_pop3_auth_host = parent::get_conf('mail', 'auth_host');
			$this->message_obj->smtp_debug = parent::get_conf('mail', 'debug');
			$this->message_obj->smtp_html_debug = parent::get_conf('mail', 'html_debug');
		} elseif (parent::get_conf('mail', 'method') == 'sendmail') {
			require_once dirname( __FILE__) . DIRECTORY_SEPARATOR . 'MimeMessage' . DIRECTORY_SEPARATOR . 'sendmail_message.php';

			$this->message_obj = new sendmail_message_class;

			$this->message_obj->delivery_mode = SENDMAIL_DELIVERY_DEFAULT;
			$this->message_obj->bulk_mail_delivery_mode = SENDMAIL_DELIVERY_QUEUE;
			$this->message_obj->sendmail_arguments = '';
		} else {
			$this->message_obj = new email_message_class;

			$this->message_obj->localhost = parent::get_conf('mail', 'workstation');
		}

		$this->set_email_header('Errors-To', Kernel::get_conf('mail', 'errors_go_to'), Kernel::get_conf('mail', 'errors_go_to'));
	}

	/**
	 *	\brief Define o valor de um item de cabeçalho
	 */
	public function set_header($header, $value) {
		$this->message_obj->SetEncodedHeader($header, $value, $GLOBALS['SYSTEM']['CHARSET']);
	}

	/**
	 *	\brief Define o valor de um item de cabeçalho
	 */
	public function set_email_header($header, $email, $name='') {
		if (is_array($email)) {
			$this->message_obj->SetMultipleEncodedEmailHeader($header, $email, $GLOBALS['SYSTEM']['CHARSET']);
		} else {
			$this->message_obj->SetEncodedEmailHeader($header, $email, $name, $GLOBALS['SYSTEM']['CHARSET']);
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
	public function to($email, $name='') {
		$this->set_email_header('To', $email, $name);
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
	public function cc($email, $name='') {
		$this->set_email_header('Cc', $email, $name);
	}

	/**
	 *	\brief Define o valor do campo Bcc
	 */
	public function bcc($email, $name='') {
		$this->set_email_header('Bcc', $email, $name);
	}

	/**
	 *	\brief Define o valor do campo From
	 */
	public function from($email, $name='') {
		$this->set_email_header('From', $email, $name);
		$this->set_header('Sender', $email, $name);
	}

	/**
	 *	\brief Define o valor do campo Subject
	 */
	public function subject($subject) {
		$this->set_header('Subject', $subject);
		$this->mail_subject = $subject;
	}

	/**
	 *	\brief Monta o corpo da mensagem
	 */
	public function body($html='', $text='') {
		$alternative_parts = array();
		// [pt-br] Monta a parte TXT
		if ($text) {
			$this->text_message = $text;
			$this->message_obj->CreateQuotedPrintableTextPart($text, $GLOBALS['SYSTEM']['CHARSET'], $this->text_part);
			$alternative_parts[] = $this->text_part;
		}
		// [pt-br] Monta a parte HTML
		if ($html) {
			$this->html_message = $html;
			$this->message_obj->CreateQuotedPrintableHTMLPart($html, $GLOBALS['SYSTEM']['CHARSET'], $this->html_part);
			$alternative_parts[] = $this->html_part;
		}
		// [pt-br] Monta as partes alternativas
		$this->message_obj->AddAlternativeMultipart($alternative_parts);
	}

	/*
	 * \brief Adiciona um anexo ao e-mail
	 */
	public function add_attach($filePost){
		$file['FileName'] = $filePost['tmp_name'];
		$file['Name'] = $filePost['name'];
		$file['Content-Type'] = $filePost['type'];

		$this->message_obj->AddFilePart($file);
		return $file;
	}

	/**
	 *	\brief Envia a mensagem
	 */
	public function send() {
		if (parent::get_conf('mail', 'method') == 'sendmail') {
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
	public function send_message($from, $from_name, $mailto, $to_name, $subject, $htmlmessage, $textmessage) {
		$this->from($from, $from_name);
		$this->to($mailto, $to_name);
		$this->subject($subject);
		$this->body($htmlmessage, $textmessage);
		return $this->send();
	}

}
