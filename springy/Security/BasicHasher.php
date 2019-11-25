<?php
/** \file
 *  Springy.
 *
 *  \brief      Classe pa geração básica de hashes.
 *  \copyright  Copyright (c) 2007-2016 Fernando Val\n
 *  \author     Allan Marques - allan.marques@ymail.com
 *  \warning    Este arquivo é parte integrante do framework e não pode ser omitido
 *  \version    0.1.1
 *  \ingroup    framework
 */

namespace Springy\Security;

/**
 * \brief Classe pa geração básica de hashes.
 */
class BasicHasher implements HasherInterface
{
    /// Sal para impossibilitar a quebra do hash
    const SALT = '865516de75706d3e9f8cdae8f66f0e0c15d6ceed';

    /**
     *  \brief Cria e retorna a string com o hash gerado da string passada por parâmetro.
     *  \param [in] (string) $stringToHash - string para gerar o hash.
     *  \param [in] (string) $times - numero de vezes para rodar o algorítmo.
     *  \return (string).
     */
    public function make($stringToHash, $times = null)
    {
        return $this->generateHash($stringToHash);
    }

    /**
     *  \brief Verifica se a string equivale ao hash.
     *  \param [in] (string) $stringToCHeck - String para comparar.
     *  \param [in] (string) $hash - Hash para comparação.
     *  \return (bool).
     */
    public function needsRehash($hash, $times = null)
    {
        return false;
    }

    /**
     *  \brief Verifica se a string necessita ser criptografada novamente.
     *  \param [in] (string) $hash - String para verificar.
     *  \param [in] (string) $times - Quantas vezes o hash deveria ter sido rodado.
     *  \return (bool).
     */
    public function verify($stringToCheck, $hash)
    {
        return $this->generateHash($stringToCheck) === $hash;
    }

    /**
     *  \brief Cria e retorna a string com o hash gerado da string passada por parâmetro.
     *  \param [in] (string) $senha - string para gerar o hash.
     *  \param [in] (string) $times - numero de vezes para rodar o algorítmo.
     *  \return (string).
     */
    public function generateHash($senha, $times = null)
    {
        $md5 = md5(strtolower(self::SALT . $senha));

        return base64_encode($md5 ^ md5($senha));
    }
}
