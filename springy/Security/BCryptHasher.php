<?php
/** @file
 *  Springy.
 *
 *  @brief      BCrypt hash generator class.
 *
 *  @copyright  Copyright (c) 2007-2018 Fernando Val
 *  @author     Allan Marques - allan.marques@ymail.com
 *
 *  @version    0.2.3.4
 *  @note       This class uses the password_compat class of Anthony Ferrara as a dependency.
 *  @ingroup    framework
 */

namespace Springy\Security;

/**
 *  @brief  BCrypt hash generator class.
 *
 *  @note   Esta classe utiliza como dependência a classe password_compat de Anthony Ferrara,
 *          que deve ser previamente instalada com Composer ou manualmente.
 */
class BCryptHasher implements HasherInterface
{
    protected $algorithm;
    protected $salt;

    /**
     *  @brief Construtor da classe.
     *
     *  @param int $algorithm - Algoritmo.
     *  @param string $salt - Sal customizado.
     */
    public function __construct($algorithm = PASSWORD_DEFAULT, $salt = '')
    {
        $this->algorithm = $algorithm;
        $this->salt = $salt;
    }

    /**
     *  @brief Cria e retorna a string com o hash gerado da string passada por parâmetro.
     *
     *  @param string $stringToHash - string para gerar o hash.
     *  @param string $times - numero de vezes para rodar o algorítmo.
     *
     *  @return string.
     */
    public function make($stringToHash, $times = 10)
    {
        return password_hash($stringToHash, $this->algorithm, $this->options($times));
    }

    /**
     *  @brief Verifica se a string equivale ao hash.
     *
     *  @param string $stringToCHeck - String para comparar.
     *  @param string $hash - Hash para comparação.
     *
     *  @return bool.
     */
    public function verify($stringToCheck, $hash)
    {
        return password_verify($stringToCheck, $hash);
    }

    /**
     *  @brief Verifica se a string necessita ser criptografada novamente.
     *
     *  @param string $hash - String para verificar.
     *  @param string $times - Quantas vezes o hash deveria ter sido rodado.
     *
     *  @return bool.
     */
    public function needsRehash($hash, $times = 10)
    {
        return password_needs_rehash($hash, $this->algorithm, $this->options($times));
    }

    /**
     *  @brief Retorna array de opções para a função de hash do BCrypt.
     *
     *  @param int $times - Numero de vezes que o algorítmo deve ser executado.
     *
     *  @return array.
     */
    protected function options($times)
    {
        $options = ['cost' => $times];

        if ($this->salt) {
            $options['salt'] = $this->salt;
        }

        return $options;
    }
}
