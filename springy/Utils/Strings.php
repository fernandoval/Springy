<?php

/**
 * Class library for string processing.
 *
 * @copyright 2007 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 * @license   https://github.com/fernandoval/Springy/blob/master/LICENSE MIT
 *
 * @version   0.13.25
 */

namespace Springy\Utils;

/**
 * Class library for string processing.
 */
class Strings
{
    /**
     * Checks whether a string is encoded in UTF-8.
     *
     * In order to check if a string is encoded correctly in utf-8,
     * I suggest the following function, that implements the
     * RFC3629 better than mb_check_encoding()
     *
     * @author <javalc6@gmail.com>
     *
     * @param string $str
     *
     * @return bool
     */
    public static function checkUTF8($str)
    {
        $len = strlen($str);
        for ($pos = 0; $pos < $len; $pos++) {
            $chr = ord($str[$pos]);

            if ($chr > 128) {
                if ($chr > 247 || $chr <= 191) {
                    return false;
                } elseif ($chr > 239) {
                    $bytes = 4;
                } elseif ($chr > 223) {
                    $bytes = 3;
                } elseif ($chr > 191) {
                    $bytes = 2;
                }

                if (($pos + $bytes) > $len) {
                    return false;
                }

                while ($bytes > 1) {
                    $pos++;
                    $chr = ord($str[$pos]);
                    if ($chr < 128 || $chr > 191) {
                        return false;
                    }
                    $bytes--;
                }
            }
        }

        return true;
    }

    /**
     * Verify that this is a valid email address.
     *
     * @param string $email    the email address.
     * @param bool   $checkDNS determines whether the existence of the email domain should be verified.
     *
     * @return bool
     */
    public static function validateEmailAddress($email, $checkDNS = true)
    {
        if (
            filter_var($email, FILTER_VALIDATE_EMAIL) &&
            preg_match('/^[a-z0-9_\-]+(\.[a-z0-9_\-]+)*@([a-z0-9_\.\-]*[a-z0-9_\-]+\.[a-z]{2,})$/i', $email, $res)
        ) {
            return $checkDNS ? checkdnsrr($res[2]) : true;
        }

        return false;
    }

    /**
     *  \brief Verifica se é um slug válido.
     */
    public static function validateSlug($txt)
    {
        return preg_match('/^[a-z0-9-]+$/', $txt);
    }

    /**
     *  \brief Valida um texto qualquer, verificanso se tem um tamanho mínimo e máximo desejado.
     *  O método também remove todas as TAGs HTML que o texto possua.
     *
     *  @param string $txt - Texto a ser validado
     *  @param int $min - Tamanho mínimo esperado para o texto (default=3).
     *  @param int $max - Tamanho máximo que o texto pode ter. Se deixar em branco permite textos de tamanho infinito.
     */
    public static function validateText(&$txt, $min = 3, $max = false)
    {
        $txt = trim(strip_tags($txt));
        $len = strlen($txt);

        return !empty($txt) && ($len >= $min) && (!$max || ($max && $len <= $max));
    }

    /**
     *  \brief Verify if a IP is from a local area network.
     */
    public static function isPraviteNetwork($userIP)
    {
        // 10.0.0.0/8 or 192.168.0.0/16
        if (substr($userIP, 0, 3) == '10.' || substr($userIP, 0, 8) == '192.168.') {
            return true;
        }

        // 172.16.0.0/12
        if (substr($userIP, 0, 4) == '172.') {
            $oct = (int) trim(substr($userIP, 4, 3), '.');
            if ($oct >= 16 && $oct <= 31) {
                return true;
            }
        }

        return false;
    }

    /**
     *  \brief Verify if given IP is valid.
     *
     *  \return Return true when given IP is valid or false if not.
     */
    public static function isValidIP($ipValue)
    {
        if (filter_var($ipValue, FILTER_VALIDATE_IP) === false || self::isPraviteNetwork($ipValue) || !strcasecmp($ipValue, 'unknown')) {
            return false;
        }

        return true;
    }

    /**
     *  \brief Retorna o endereço IP remoto real.
     *
     *  Existem certas situações em que o verdadeiro IP do visitante fica mascarado quando o servidor de aplicação
     *  está por trás de um firewall ou balanceador de carga. Nesses casos é necessário fazer certas verificações em
     *  lugar de pegar apenas o valor da vairável $_SERVER['REMOTE_ADDR'].
     *
     *  Este método tenta recuperar o real IP do visitante, fazendo verificações e garantindo que nenhum valor de IP
     *  inválido seja retornado.
     *
     *  \return Retorna uma string contendo o IP real do host que fez a requisição.
     */
    public static function getRealRemoteAddr()
    {
        // Check if behind a proxy
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && strcasecmp($_SERVER['HTTP_X_FORWARDED_FOR'], 'unknown')) {
            foreach (explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']) as $val) {
                $val = trim($val);

                if (self::isValidIP($val)) {
                    return $val;
                }
            }
        }

        // Check header HTTP_X_REAL_IP
        if (isset($_SERVER['HTTP_X_REAL_IP']) && self::isValidIP(trim($_SERVER['HTTP_X_REAL_IP']))) {
            return trim($_SERVER['HTTP_X_REAL_IP']);
        }

        // Check header HTTP_CLIENT_IP
        if (isset($_SERVER['HTTP_CLIENT_IP']) && self::isValidIP(trim($_SERVER['HTTP_CLIENT_IP']))) {
            return trim($_SERVER['HTTP_CLIENT_IP']);
        }

        // Check header HTTP_CLIENT_IP
        if (isset($_SERVER['REMOTE_ADDR']) && self::isValidIP(trim($_SERVER['REMOTE_ADDR']))) {
            return trim($_SERVER['REMOTE_ADDR']);
        }

        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
    }

    /**
     *  \brief Gena um identificador único global (globally unique identifier - GUID).
     *
     *  \note Esta função foi copiada da contribuição de Alix Axel para a documentação do PHP
     *  em http://php.net/manual/en/function.com-create-guid.php
     */
    public static function guid()
    {
        if (function_exists('com_create_guid') === true) {
            return trim(com_create_guid(), '{}');
        }

        return sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
    }

    /**
     *  \brief Converte um IPv4 em valor inteiro.
     *
     *  @param string $ipv4 - endereço ip
     *
     *  @return Retorna um valor inteiro
     */
    public static function ipv4ToNumber($ipv4)
    {
        // Sepada os octetos do IP
        $parts = explode('.', $ipv4);
        if (count($parts) < 4) {
            $parts = [127, 0, 0, 1];
        }

        // Calcula o valor do IP
        return (16777216 * (int) $parts[0]) + (65536 * (int) $parts[1]) + (256 * (int) $parts[2]) + (int) $parts[3];
    }

    /**
     *  \brief Troca caracteres acentuados por não acentuado.
     */
    public static function removeAccentedChars($txt)
    {
        if ((function_exists('mb_check_encoding') && mb_check_encoding($txt, 'UTF-8')) || self::checkUTF8($txt)) {
            return Strings_UTF8::removeAccentedChars($txt);
        }

        return Strings_ANSI::removeAccentedChars($txt);
    }

    /* As funções abaixo ainda estão em processo de migração para o framework e não devem ser utilizadas */

    /*
     * @param[in] (string)$numero - variável a ser validado
     * @param[in] (string)$tamanho - quantidade máxima de caracteres [0-9] aceitos. Se for passado vazio (''), será infinito
     * @param[in] (string)$minimo - quantidade mínima de caracteres [0-9] aceitos
     * @param[in] (boolean|int|string)$float - Se for === false, não poderá ser flutuante. Se for int, será o número máximo de caracteres aceitos após o ponto. Se for vazio ('') será infinito
     * @param[in] (boolean)$negativo - informa se o número poderá ser negativo
     */

    public static function numero($numero, $tamanho = '', $minimo = 1, $float = false, $negativo = false)
    {
        return preg_match('/^' . ($negativo ? '[\-]?' : '') . '[0-9]{' . $minimo . ',' . $tamanho . '}' . ($float !== false ? '\.[0-9]{1,' . $float . '}' : '') . '$/', $numero);
    }

    /**
     *  \brief Valida uma data no formato dd/mm/yyyy.
     *
     *  Só serão consideradas válidas datas de 01/01/1900 até 31/12/2199.
     *
     *  @param[in] (string)$data - data no formato d/m/Y
     */
    public static function data($data)
    {
        $d = \DateTime::createFromFormat('d/m/Y', $data);

        return $d && $d->format('d/m/Y') == $data;
    }

    /**
     * Valida uma hora no formato HH:MM ou HH:MM:SS.
     *
     * @param string $hora
     * @param bool   $segundos Valida os segundos, default: false
     *
     * @return bool
     */
    public static function hora($hora, $segundos = false)
    {
        if ($segundos && !preg_match('/^([0-1][0-9]|[2][0-3]|[0-9]):([0-5][0-9]|[0-9]):([0-5][0-9]|[0-9])$/', $hora)) {
            return false;
        } elseif (!$segundos && !preg_match('/^([0-1][0-9]|[2][0-3]|[0-9]):([0-5][0-9]|[0-9])$/', $hora)) {
            return false;
        }

        return true;
    }

    /**
     *  \brief Valida o tamanho de uma string.
     *
     *  Essa função não tem muito sentido em existir. Está sendo mantida apenas
     *  para compatibilidade com versões anteriores do framework.
     *
     *  Ela foi criada por causa da função nunca agregada, mas mantida sem
     *  documentação, denominada senha, que verifica apenas os tamanhos
     *  mínimo e máximo de um string fornecido.
     *
     *  Sugestão: elaborar uma função ou classe melhor para verificação de
     *  senhas, que, preferencialmente, tenha recursos como o de teste de força
     *  da senha.
     *
     *  \param (string)$string - String a ser verificado
     *  \param (int)$minSize - tamanho mínimo aceito. Padrão = 5
     *  \param (int)$maxSize - tamanho máximo aceito. Padrão = 16
     */
    public static function sizeMatch($string, $minSize = 5, $maxSize = 16)
    {
        return preg_match('/^(.){' . $minSize . ',' . $maxSize . '}$/', $string);
    }

    public static function cep(&$cep)
    {
        $cep = preg_replace('/[-.]/', '', $cep);

        return preg_match('/^[0-9]{8}$/', $cep);
    }

    public static function telefone(&$ddd, &$telefone)
    {
        $telefone = preg_replace('/[^0-9]/', '', $telefone);
        $len = strlen($ddd . $telefone);

        return ($len == 10 || $len == 11) && is_numeric($ddd . $telefone);
    }

    /* *** */

    private static function checkIsFake($string, $length)
    {
        for ($i = 0; $i <= 9; $i++) {
            $fake = str_pad('', $length, $i);
            if ($string === $fake) {
                return true;
            }
        }

        return false;
    }

    /**
     * @brief Verify a brazilian document number CPF.
     *
     * @param string $cpf the CPF number.
     */
    public static function cpf($cpf)
    {
        if (!preg_match('/^[0-9]{3}\.?[0-9]{3}\.?[0-9]{3}\-?[0-9]{2}$/', $cpf)) {
            return false;
        }

        $cpf = preg_replace('/[^0-9]/', '', $cpf);

        if (self::checkIsFake($cpf, 11)) {
            return false;
        }

        $digito = [];

        // PEGA O DIGITO VERIFIACADOR
        $dv_informado = substr($cpf, 9, 2);

        for ($i = 0; $i <= 8; $i++) {
            $digito[$i] = intval(substr($cpf, $i, 1));
        }

        // CALCULA O VALOR DO 10º DIGITO DE VERIFICAÇÃO
        $posicao = 10;
        $soma = 0;

        for ($i = 0; $i <= 8; $i++) {
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

    public static function cnpj(&$cnpj)
    {
        $cnpj = str_replace('/', '', str_replace('-', '', str_replace('.', '', $cnpj)));

        if (empty($cnpj) || strlen($cnpj) != 14 || !is_numeric($cnpj) || self::checkIsFake($cnpj, 14)) {
            return false;
        }
        $sum = 0;
        $rev_cnpj = strrev(substr($cnpj, 0, 12));
        $multiplier = 0;
        for ($i = 0; $i <= 11; $i++) {
            $i == 0 ? $multiplier = 2 : $multiplier;
            $i == 8 ? $multiplier = 2 : $multiplier;
            $multiply = ($rev_cnpj[$i] * $multiplier);
            $sum += $multiply;
            $multiplier++;
        }

        $rest = $sum % 11;
        if ($rest == 0 || $rest == 1) {
            $dv1 = 0;
        } else {
            $dv1 = 11 - $rest;
        }

        $sub_cnpj = substr($cnpj, 0, 12);
        $rev_cnpj = strrev($sub_cnpj . $dv1);

        unset($sum);
        $sum = 0;
        $multiplier = 0;
        for ($i = 0; $i <= 12; $i++) {
            $i == 0 ? $multiplier = 2 : $multiplier;
            $i == 8 ? $multiplier = 2 : $multiplier;
            $multiply = ($rev_cnpj[$i] * $multiplier);
            $sum += $multiply;
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
