<?php
/** \file
 *  Springy.
 *
 *  \brief      Classe pa geração de hashes via BCrypt.
 *  \copyright  Copyright (c) 2007-2016 Fernando Val
 *  \author     Allan Marques - allan.marques@ymail.com
 *  \warning    Este arquivo é parte integrante do framework e não pode ser omitido
 *  \version    0.2.3
 *  \note       Esta biblioteca utiliza como dependência a classe password_compat de Anthony Ferrara.
 *  \ingroup    framework
 */
namespace Springy\Security;

//require sysconf('3RDPARTY_PATH').DS.'password_compat'.DS.'lib'.DS.'password.php';

/**
 *  \brief  Classe pa geração de hashes via BCrypt.
 *
 *  \note   Esta classe utiliza como dependência a classe password_compat de Anthony Ferrara,
 *          que deve ser previamente instalada com Composer ou manualmente.
 */
class BCryptHasher implements HasherInterface
{
    protected $algorithm;
    protected $salt;

    /**
     *  \brief Construtor da classe.
     *  \param [in] (int) $algorithm - Algoritmo.
     *  \param [in] (string) $salt - Sal customizado.
     */
    public function __construct($algorithm = PASSWORD_DEFAULT, $salt = '')
    {
        $this->algorithm = $algorithm;
        $this->salt = $salt;
    }

    /**
     *  \brief Cria e retorna a string com o hash gerado da string passada por parâmetro.
     *  \param [in] (string) $stringToHash - string para gerar o hash.
     *  \param [in] (string) $times - numero de vezes para rodar o algorítmo.
     *  \return (string).
     */
    public function make($stringToHash, $times = 10)
    {
        return password_hash($stringToHash, $this->algorithm, $this->options($times));
    }

    /**
     *  \brief Verifica se a string equivale ao hash.
     *  \param [in] (string) $stringToCHeck - String para comparar.
     *  \param [in] (string) $hash - Hash para comparação.
     *  \return (bool).
     */
    public function verify($stringToCheck, $hash)
    {
        return password_verify($stringToCheck, $hash);
    }

    /**
     *  \brief Verifica se a string necessita ser criptografada novamente.
     *  \param [in] (string) $hash - String para verificar.
     *  \param [in] (string) $times - Quantas vezes o hash deveria ter sido rodado.
     *  \return (bool).
     */
    public function needsRehash($hash, $times = 10)
    {
        return password_needs_rehash($hash, $this->algorithm, $this->options($times));
    }

    /**
     *  \brief Retorna array de opções para a função de hash do BCrypt.
     *  \param [in] (int) $times - Numero de vezes que o algorítmo deve ser executado.
     *  \return (array).
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
