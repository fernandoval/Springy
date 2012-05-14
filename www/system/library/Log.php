<?php
/**
 *	FVAL PHP Framework for Web Applications\n
 *	Copyright (c) 2007-2012 FVAL Consultoria e Informática Ltda.\n
 *	Copyright (c) 2007-2012 Fernando Val
 *
 *	\warning Este arquivo é parte integrante do framework e não pode ser omitido
 *
 *	\version 0.9.2
 *
 *	\brief Classe para geração de saídas em logs de eventos
 *
 *	\author Fernando Val <fernando.val@gmail.com>
 */

class Log extends Kernel {
	/**
	 *	\brief Escreve uma informação no log
	 *
	 *	@param[in] $message \c string mensagem a ser gravada no log de erros do PHP
	 */
	public static function Write($message) {
		/// Pega o IP do usuário
		$source_ip = empty($_SERVER['REMOTE_ADDR']) ? '127.0.0.1' : $_SERVER['REMOTE_ADDR'];
		/// Pega a página onde ocorreu o evento
		$url = URI::get_uri_string();

		//$message = Strings::remove_accented_chars($message);

		/// Monta a linha do evento
		$evt_message = date('Y-m-d H:i:s') . ' ' . $source_ip . ' ' . $url . ' ' . $message;

		error_log($evt_message);
	}
}
