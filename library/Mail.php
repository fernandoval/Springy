<?php
/*  ------------------------------------------------------------------------------------ --- -- -
	FVAL PHP Framework for Web Sites

	Copyright (C) 2009 FVAL - Consultoria e Informática Ltda.
	Copyright (C) 2009 Fernando Val
	Copyright (C) 2009 Lucas Cardozo

	http://www.fval.com.br

	Developer team:
		Fernando Val  - fernando.val@gmail.com
		Lucas Cardozo - lucas.cardozo@gmail.com

	Framework version:
		1.0.0

	Script version:
		1.0.0

	This script:
		Framework mail class
	------------------------------------------------------------------------------------ --- -- - */

require_once dirname( __FILE__) . DIRECTORY_SEPARATOR . 'MimeMessage' . DIRECTORY_SEPARATOR . 'email_message.php';

class Mail extends Kernel {
	private static $message_obj = NULL;
	private static $html_part = NULL;
	private static $text_part = NULL;
	private static $html_message = '';
	private static $text_message = '';
	private static $mail_subject = '';
	private static $mail_to = '';
	private static $mail_name = '';

	/*  -------------------------------------------------------------------------------- --- -- -
		[pt-br] Construtor da classe
	    -------------------------------------------------------------------------------- --- -- - */
	function __construct() {
		if (parent::get_conf('mail', 'method') == 'smtp') {
			require_once dirname( __FILE__) . DIRECTORY_SEPARATOR . 'MimeMessage' . DIRECTORY_SEPARATOR . 'smtp_message.php';
			require_once dirname( __FILE__) . DIRECTORY_SEPARATOR . 'Smtp' . DIRECTORY_SEPARATOR . 'smtp.php';

			if (parent::get_conf('mail', 'ssl') || parent::get_conf('mail', 'starttls')) {
				require_once dirname( __FILE__) . DIRECTORY_SEPARATOR . 'Sasl' . DIRECTORY_SEPARATOR . 'sasl.php';
			}

			self::$message_obj = new smtp_message;

			self::$message_obj->localhost = parent::get_conf('mail', 'workstation');
			self::$message_obj->smtp_host = parent::get_conf('mail', 'host');
			self::$message_obj->smtp_port = parent::get_conf('mail', 'port');
			self::$message_obj->smtp_ssl = parent::get_conf('mail', 'ssl');
			self::$message_obj->smtp_start_tls = parent::get_conf('mail', 'starttls');
			self::$message_obj->smtp_direct_delivery = parent::get_conf('mail', 'direct_delivery');
			self::$message_obj->smtp_exclude_address = parent::get_conf('mail', 'exclude_address');
			self::$message_obj->smtp_user = parent::get_conf('mail', 'user');
			self::$message_obj->smtp_realm = parent::get_conf('mail', 'realm');
			self::$message_obj->smtp_workstation = parent::get_conf('mail', 'workstation');
			self::$message_obj->smtp_password = parent::get_conf('mail', 'pass');
			self::$message_obj->smtp_pop3_auth_host = parent::get_conf('mail', 'auth_host');
			self::$message_obj->smtp_debug = parent::get_conf('mail', 'debug');
			self::$message_obj->smtp_html_debug = parent::get_conf('mail', 'html_debug');
		} elseif (parent::get_conf('mail', 'method') == 'sendmail') {
			require_once dirname( __FILE__) . DIRECTORY_SEPARATOR . 'MimeMessage' . DIRECTORY_SEPARATOR . 'sendmail_message.php';

			self::$message_obj = new sendmail_message_class;

			self::$message_obj->delivery_mode = SENDMAIL_DELIVERY_DEFAULT;
			self::$message_obj->bulk_mail_delivery_mode = SENDMAIL_DELIVERY_QUEUE;
			self::$message_obj->sendmail_arguments = '';
		} else {
			self::$message_obj = new email_message_class;
		}

		self::set_email_header('Errors-To', Kernel::get_conf('mail', 'errors_go_to'), Kernel::get_conf('mail', 'errors_go_to'));
	}

	/*  -------------------------------------------------------------------------------- --- -- -
		[pt-br] Define o valor de um item de cabeçalho
	    -------------------------------------------------------------------------------- --- -- - */
	public static function set_header($header, $value) {
		self::$message_obj->SetEncodedHeader($header, $value, $GLOBALS['SYSTEM']['CHARSET']);
	}

	/*  -------------------------------------------------------------------------------- --- -- -
		[pt-br] Define o valor de um item de cabeçalho
	    -------------------------------------------------------------------------------- --- -- - */
	public static function set_email_header($header, $email, $name='') {
		if (is_array($email)) {
			self::$message_obj->SetMultipleEncodedEmailHeader($header, $email, $GLOBALS['SYSTEM']['CHARSET']);
		} else {
			self::$message_obj->SetEncodedEmailHeader($header, $email, $name, $GLOBALS['SYSTEM']['CHARSET']);
		}
	}

	/*  -------------------------------------------------------------------------------- --- -- -
		[pt-br] Define o valor do campo To
	    -------------------------------------------------------------------------------- --- -- - */
	public static function to($email, $name='') {
		self::set_email_header('To', $email, $name);
		if (is_array($email)) {
			self::$mail_to = key($mail[0]);
		} else {
			self::$mail_to = $mail;
		}
	}

	/*  -------------------------------------------------------------------------------- --- -- -
		[pt-br] Define o valor do campo From
	    -------------------------------------------------------------------------------- --- -- - */
	public static function from($email, $name='') {
		self::set_email_header('From', $email, $name);
		self::set_header('Sender', $email, $name);
	}

	/*  -------------------------------------------------------------------------------- --- -- -
		[pt-br] Define o valor do campo Subject
	    -------------------------------------------------------------------------------- --- -- - */
	public static function subject($subject) {
		self::set_header('Subject', $subject);
		self::$mail_subject = $subject;
	}

	/*  -------------------------------------------------------------------------------- --- -- -
		[pt-br] Monta o corpo da mensagem
	    -------------------------------------------------------------------------------- --- -- - */
	public static function body($html='', $text='') {
		$alternative_parts = array()
		// [pt-br] Monta a parte HTML
		if ($html) {
			self::$html_message = $html;
			self::$message_obj->CreateQuotedPrintableHTMLPart($html, $GLOBALS['SYSTEM']['CHARSET'], self::$html_part);
			$alternative_parts[] = self::$html_part;
		}
		// [pt-br] Monta a parte TXT
		if ($text) {
			self::$text_message = $text;
			self::$message_obj->CreateQuotedPrintableTextPart($text, $GLOBALS['SYSTEM']['CHARSET'], self::$text_part);
			$alternative_parts[] = self::$text_part;
		}
		// [pt-br] Monta as partes alternativas
		self::$message_obj->AddAlternativeMultipart($alternative_parts);
	}

	/*  -------------------------------------------------------------------------------- --- -- -
		[pt-br] Envia a mensagem
	    -------------------------------------------------------------------------------- --- -- - */
	public static function send() {
		if (parent::get_conf('mail', 'method') == 'sendmail') {
			$error = self::$message_obj->Mail(self::$mail_to, self::$mail_subject, self::$text_message, '', '');
		} else {
			$error = self::$message_obj->Send();
		}
	}
}
?>