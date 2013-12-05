<?php
/**	\file
 *	FVAL PHP Framework for Web Applications
 *
 *	\copyright Copyright (c) 2007-2013 FVAL Consultoria e Informática Ltda.\n
 *	\copyright Copyright (c) 2007-2013 Fernando Val\n
 *	\copyright Copyright (c) 2009-2013 Lucas Cardozo
 *
 *	\brief		Classe com métodos para diversos tipos de tratamento e validação de dados string
 *	\warning	Este arquivo é parte integrante do framework e não pode ser omitido
 *	\version	0.5.7
 *  \author		Fernando Val  - fernando.val@gmail.com
 *	\ingroup	framework
 */

class Strings {
	/**
	 *	\brief Verifica se uma string está codificada em UTF-8
	 *
	 *	@param $str - string
	 *
	 *	\note Esta foi escrita por javalc6@gmail.com e disponibilizada pelo autor na documentação do PHP
	 *
	 *	In order to check if a string is encoded correctly in utf-8, I suggest the following function, that implements the RFC3629 better than mb_check_encoding()
	 */
	public static function checkUTF8($str) {
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
	 *	@param Boolean $checkDNS - determina se a existência do domínio do email deve ser verificado
	 */
	public static function validateEmailAddress($email, $checkDNS=true) {
		if (preg_match('/^[a-z0-9_\-]+(\.[a-z0-9_\-]+)*@([a-z0-9_\.\-]*[a-z0-9_\-]+\.[a-z]{2,4})$/i', $email, $res)) {
			return $checkDNS ? checkdnsrr($res[2]) : true;
		}
		return false;
	}

	/**
	 *	\brief Verifica se é um slug válido
	 */
	public static function validateSlug($txt) {
		return preg_match('/^[a-z0-9-]+$/', $txt);
	}

	/**
	 *	\brief Valida um texto qualquer, verificanso se tem um tamanho mínimo e máximo desejado.
	 *	O método também remove todas as TAGs HTML que o texto possua.
	 *
	 *	@param String $txt - Texto a ser validado
	 *	@param Int $min - Tamanho mínimo esperado para o texto (default=3).
	 *	@param Int $max - Tamanho máximo que o texto pode ter. Se deixar em branco permite textos de tamanho infinito.
	 */
	public static function validateText(&$txt, $min=3, $max=false) {
		$txt = trim(strip_tags($txt));
		$len = strlen($txt);
		return (!empty($txt) && ($len >= $min) && (!$max || ($max && $len <= $max)));
	}

	/**
	 *	\brief Retorna o endereço IP remoto real
	 */
	public static function getRealRemoteAddr() {
		// Pega o IP que vem por trás de proxies
		// $ApacheHeader = apache_request_headers();
		// if (!empty($_SERVER['HTTP_X_FORWARDED_FOR']) || !empty($ApacheHeader['X-Forwarded-For']))
		if (empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			$ip = "";
		} else {
			// $httpip = empty($_SERVER['HTTP_X_FORWARDED_FOR']) ? $ApacheHeader['X-Forwarded-For'] : $_SERVER['HTTP_X_FORWARDED_FOR'];
			$httpip = $_SERVER['HTTP_X_FORWARDED_FOR'];
			if (strpos($httpip, ',')) {
				$httpip = explode(',', $httpip);
				while (list(, $val) = each($httpip)) {
					$val = trim($val);
					if (substr($val, 0, 3) != '10.' && substr($val, 0, 8) != '192.168.' && substr($val, 0, 7) != '172.16.') {
						$ip = $val;
						break;
					}
				}
			} else {
				$ip = $httpip;
			}
		}
		// Verifica se ainda não chegou ao IP real
		if (empty($ip) || substr($ip, 0, 3) == '10.' || substr($ip, 0, 8) == '192.168.' || substr($ip, 0, 7) == '172.16.') {
			if (!empty($_SERVER['HTTP_X_REAL_IP'])) {
				$ip = $_SERVER['HTTP_X_REAL_IP'];
			} else {
				$ip = $_SERVER['REMOTE_ADDR'];
			}
		}

		return $ip;
	}

	/**
	 *	\brief Gena um identificador único global (globally unique identifier - GUID)
	 *
	 *	\note Esta função foi copiada da contribuição de Alix Axel para a documentação do PHP
	 *	em http://php.net/manual/en/function.com-create-guid.php
	 */
	public static function guid() {
		if (function_exists('com_create_guid') === true) {
			return trim(com_create_guid(), '{}');
		}

		return sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
	}

	/**
	 *	\brief Converte um IPv4 em valor inteiro
	 *
	 *	@param String $ip - endereço ip
	 *	@return Retorna um valor inteiro
	 */
	public static function ipv4ToNumber($ip) {
		// Sepada os octetos do IP
		$m = explode('.', $ip);
		if (count($m) < 4) {
			$m = array(127, 0, 0, 1);
		}

		// Calcula o valor do IP
		return (16777216 * (int)$m[0]) + (65536 * (int)$m[1]) + (256 * (int)$m[2]) + (int)$m[3];
	}

	/**
	 *	\brief Troca caracteres acentuados por não acentuado
	 */
	public static function removeAccentedChars($txt) {
		if ((function_exists('mb_check_encoding') && mb_check_encoding($txt, 'UTF-8')) || self::checkUTF8($txt)) {
			return Strings_UTF8::removeAccentedChars($txt);
		}

		return Strings_ANSI::removeAccentedChars($txt);
	}

	/* As funções abaixo ainda estão em processo de migração para o framework e não devem ser utilizadas */

	public static function bigText(&$txt, $notStripTags='') {
		$txt = trim(strip_tags($txt, $notStripTags));
		return !empty($txt);
	}
	
	/*
	 * 
	 * @param[in] (string)$numero - variável a ser validado
	 * @param[in] (string)$tamanho - quantidade máxima de caracteres [0-9] aceitos. Se for passado vazio (''), será infinito
	 * @param[in] (string)$minimo - quantidade mínima de caracteres [0-9] aceitos
	 * @param[in] (boolean|int|string)$float - Se for === false, não poderá ser flutuante. Se for int, será o número máximo de caracteres aceitos após o ponto. Se for vazio ('') será infinito
	 * @param[in] (boolean)$negativo - informa se o número poderá ser negativo
	 */
	public static function numero($numero, $tamanho='', $minimo=1, $float=false, $negativo=false) {
		return preg_match('/^' . ($negativo ? '[\-]?' : '') . '[0-9]{'.$minimo.',' . $tamanho . '}' . ($float !== false ? '\.[0-9]{1,' . $float . '}' : '') . '$/', $numero);
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
		$len = strlen($ddd . $telefone);
		return ($len == 10 || $len == 11) && is_numeric($ddd . $telefone);
	}

	/* *** */

	public static function form_id($to, $code) {
		return (Session::get('_FORM_CAPTCHA_' . strtoupper($to)) == base64_decode($code));
	}

	public static function generate_form_id($to) {
		$id = md5($to) . md5(Session::getId()) . md5(uniqid(rand(), true));
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

	/**
	 * Método que valida a google maps.
	 *
	 * @access public
	 * @param string $url
	 * @return bool true||false
	 */
	public static function validarMaps( $url ) {
		$domin = "";
		$urls = explode("/", $url);

		foreach( $urls as $url )
		{
			if( preg_match('/maps.google.com.br|maps.google.com/i', $url ) )
				return true;
		}//forech

		return false;

	}//function

	/**
	 * função que retorna a quantidade de dias entre 2 datas
	 *
	 * @access public
	 * @param string $data1, $data 2, $tipo 31/12/2013 ou 2012-12-31
	 * @return mktime $data
	 */

	public static function qtdDias($dataIni, $dataFim) {
		if( (!isset($dataIni) || empty($dataIni)) || (!isset($dataFim) || empty($dataFim))) {
			return false;
		}

		if(strpos($dataIni, '/')) {
			$partes = explode('/', $dataIni);
			$time_inicial = mktime(0, 0, 0, $partes[1], $partes[0], $partes[2]);

			$partes = explode('/', $dataFim);
			$time_final = mktime(0, 0, 0, $partes[1], $partes[0], $partes[2]);
		} elseif(strpos($dataIni, '-')) {
			$partes = explode('-', $dataIni);
			$time_inicial = mktime(0, 0, 0, $partes[1], $partes[2], $partes[0]);

			$partes = explode('-', $dataFim);
			$time_final = mktime(0, 0, 0, $partes[1], $partes[2], $partes[0]);

		} else {
			return false;
		}

		// Calcula a diferença de segundos entre as duas datas:
		$diferenca = $time_final - $time_inicial; // segundos

		// Calcula a diferença de dias
		return (int)floor( $diferenca / 86400); // 24 horas  = 86400 segundos

	}
}
