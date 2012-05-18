<?php
/**
 *	FVAL PHP Framework for Web Applications\n
 *	Copyright (c) 2007-2012 FVAL Consultoria e Informática Ltda.\n
 *	Copyright (c) 2007-2012 Fernando Val\n
 *	Copyright (c) 2009-2012 Lucas Cardozo
 *
 *	\warning Este arquivo é parte integrante do framework e não pode ser omitido
 *
 *	\version 0.1.3
 *
 *	\brief Classe com métodos para diversos tipos de tratamento e validação de dados string
 */

class Strings extends Kernel {
	/**
	 *	\brief Verifica se uma string está codificada em UTF-8
	 *
	 *	@param $str - string
	 *
	 *	\note Esta foi escrita por javalc6@gmail.com e disponibilizada pelo autor na documentação do PHP
	 *
	 *	In order to check if a string is encoded correctly in utf-8, I suggest the following function, that implements the RFC3629 better than mb_check_encoding()
	 */
	public static function check_utf8($str) {
		$len = strlen($str);
		for($i = 0; $i < $len; $i++){
			$c = ord($str[$i]);
			if ($c > 128) {
				if (($c > 247)) return false;
				elseif ($c > 239) $bytes = 4;
				elseif ($c > 223) $bytes = 3;
				elseif ($c > 191) $bytes = 2;
				else return false;
				if (($i + $bytes) > $len) return false;
				while ($bytes > 1) {
					$i++;
					$b = ord($str[$i]);
					if ($b < 128 || $b > 191) return false;
					$bytes--;
				}
			}
		}
		return true;
	}

	/**
	 *	\brief Verifica se um endereço de email
	 *
	 *	@param String $email - email a ser validado
	 *	@param Boolean $checkdns - determina se a existência do domínio do email deve ser verificado
	 */
	public static function check_email_address($email, $checkdns=true) {
		if (preg_match('/^[a-z0-9_\-]+(\.[a-z0-9_\-]+)*@([a-z0-9_\.\-]*[a-z0-9_\-]+\.[a-z]{2,4})$/i', $email, $res)) {
			return $checkdns ? checkdnsrr($res[2]) : true;
		}
		return false;
	}

	/**
	 *	\brief Valida um texto qualquer, verificanso se tem um tamanho mínimo e máximo desejado.
	 *	O método também remove todas as TAGs HTML que o texto possua.
	 *
	 *	@param String $txt - Texto a ser validado
	 *	@param Int $min - Tamanho mínimo esperado para o texto (default=3).
	 *	@param Int $max - Tamanho máximo que o texto pode ter. Se deixar em branco permite textos de tamanho infinito.
	 */
	public static function check_valid_text(&$txt, $min=3, $max=false) {
		$txt = trim(strip_tags($txt));
		$len = strlen($txt);
		return (!empty($txt) && ($len >= $min) && (!$max || ($max && $len <= $max)));
	}

	/**
	 *	\brief Troca caracteres acentuados por não acentuado
	 */
	public static function remove_accented_chars($txt) {
		if ((function_exists('mb_check_encoding') && mb_check_encoding($txt, 'UTF-8')) || self::check_utf8($txt)) {
			return Strings_UTF8::remove_accented_chars($txt);
		}

		return Strings_ANSI::remove_accented_chars($txt);
	}


	/* As funções abaixo ainda estão em processo de migração para o framework e não devem ser utilizadas */

	public static function bigText(&$txt, $notStripTags='') {
		$txt = trim(strip_tags($txt, $notStripTags));
		return !empty($txt);
	}

	public static function numero($numero, $tamanho='', $minimo=1, $float=false) {
		return preg_match('/^[0-9]{'.$minimo.',' . $tamanho . '}' . ($float ? '\.[0-9]{1,' . $float . '}' : '') . '$/', $numero);
	}

	/**
	 *	\brief Valida uma data no formato dd/mm/yyyy
	 *
	 *	Só serão consideradas válidas datas de 01/01/1900 até 31/12/2199.
	 *
	 *	@param[in] (string)$data - data no formato d/m/Y
	 */
	public static function data($data) {
		if (!preg_match('/^(0[1-9]|[1-2][0-9]|3[0-1])\/(0[1-9]|1[0-2])\/((19|20|21)[0-9]{2})$/', $data, $res)) {
			return false;
		}

		if (date('d/m/Y', mktime(0, 0, 0, $res[2], $res[1], $res[3])) != $data) {
			return false;
		}

		return true;
	}

	public static function senha($senha) {
		return preg_match('/^(.){6,12}$/', $senha);
	}

	public static function ie($ie) {
		$ie = str_replace('/', '', str_replace('-', '', str_replace('.', '', $ie)));
		return is_numeric($ie);
	}

	public static function cep(&$cep) {
		$cep = preg_replace('/[-.]/', '', $cep);
		return preg_match('/^[0-9]{8}$/', $cep);
	}

	public static function telefone(&$ddd, &$telefone) {
		$telefone = str_replace('-', '', $telefone);
		return strlen($ddd . $telefone) == 10 && is_numeric($ddd . $telefone);
	}

	/* *** */

	public static function form_id($to, $code) {
		return (Session::get('_FORM_CAPTCHA_' . strtoupper($to)) == base64_decode($code));
	}

	public static function generate_form_id($to) {
		$id = md5($to) . md5(Session::get_session_id()) . md5(uniqid(rand(), true));
		Session::set('_FORM_CAPTCHA_' . strtoupper($to), $id);
		return base64_encode($id);
	}

	private static function checkIsFake($string, $length) {
		for ($i=0; $i<=9; $i++) {
			$fake = str_pad('', $length, $i);
			if($string === $fake) {
				return true;
			}
		}

		return false;
	}

	public static function cpf(&$cpf) {
		$cpf = preg_replace('/[^0-9]/', '', $cpf);
 		// VERIFICA SE O QUE FOI INFORMADO É NÚMERO
        if (!trim($cpf) || !is_numeric($cpf) || self::checkIsFake($cpf, 11)) {
			return false;
		}

		// PEGA O DIGITO VERIFIACADOR
		$dv_informado = substr($cpf, 9, 2);

		for($i = 0; $i <= 8; $i++) {
			$digito[$i] = substr($cpf, $i, 1);
		}

		// CALCULA O VALOR DO 10º DIGITO DE VERIFICAÇÃO
		$posicao = 10;
		$soma = 0;

		for($i = 0; $i <= 8; $i++) {
			$soma = $soma + $digito[$i] * $posicao;
			$posicao = $posicao - 1;
		}

		$digito[9] = $soma % 11;

		if ($digito[9] < 2) {
			$digito[9] = 0;
		} else {
			$digito[9] = 11 - $digito[9];
		}
		// CALCULA O VALOR DO 11º DIGITO DE VERIFICAÇÃO
		$posicao = 11;
		$soma = 0;

		for ($i = 0; $i <= 9; $i++) {
			$soma = $soma + $digito[$i] * $posicao;
			$posicao = $posicao - 1;
		}

		$digito[10] = $soma % 11;

		if ($digito[10] < 2) {
			$digito[10] = 0;
		} else {
			$digito[10] = 11 - $digito[10];
		}

		// VERIFICA SE O DV CALCULADO É IGUAL AO INFORMADO
		$dv = $digito[9] * 10 + $digito[10];
		if ($dv != $dv_informado) {
			return false;
		}

		return true;

	}

	public static function cnpj(&$cnpj) {
		$cnpj = str_replace('/', '', str_replace('-', '', str_replace('.', '', $cnpj)));

		if(empty($cnpj) || strlen($cnpj) != 14 || !is_numeric($cnpj) || self::checkIsFake($cnpj, 14)) {
			return false;
		}
		$sum = '';
		$rev_cnpj = strrev(substr($cnpj, 0, 12));
		for ($i = 0; $i <= 11; $i++) {
			$i == 0 ? $multiplier = 2 : $multiplier;
			$i == 8 ? $multiplier = 2 : $multiplier;
			$multiply = ($rev_cnpj[$i] * $multiplier);
			$sum = $sum + $multiply;
			$multiplier++;

		}

		$rest = $sum % 11;
		if ($rest == 0 || $rest == 1) {
			$dv1 = 0;
		} else {
			$dv1 = 11 - $rest;
		}

		$sub_cnpj = substr($cnpj, 0, 12);
		$rev_cnpj = strrev($sub_cnpj.$dv1);

		unset($sum);
		$sum = '';
		for ($i = 0; $i <= 12; $i++) {
			$i == 0 ? $multiplier = 2 : $multiplier;
			$i == 8 ? $multiplier = 2 : $multiplier;
			$multiply = ($rev_cnpj[$i] * $multiplier);
			$sum = $sum + $multiply;
			$multiplier++;

		}

		$rest = $sum % 11;

		if ($rest == 0 || $rest == 1) {
			$dv2 = 0;
		} else {
			$dv2 = 11 - $rest;
		}

		if ($dv1 != $cnpj[12] || $dv2 != $cnpj[13]) {
			return false;
		}

		return true;
	}
}
