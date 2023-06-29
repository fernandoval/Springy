<?php

/**
 * Classe para geração básica de hashes.
 *
 * @copyright 2007 Fernando Val
 * @author    Allan Marques <allan.marques@ymail.com>
 * @author    Fernando Val <fernando.val@gmail.com>
 * @license   https://github.com/fernandoval/Springy/blob/master/LICENSE MIT
 *
 * @version   0.1.2
 */

namespace Springy\Security;

class BasicHasher implements HasherInterface
{
    // Sal para impossibilitar a quebra do hash
    protected const SALT = '865516de75706d3e9f8cdae8f66f0e0c15d6ceed';

    /**
     * Cria e retorna a string com o hash gerado da string passada por parâmetro.
     *
     * @param string $stringToHash string para gerar o hash.
     * @param string $times        numero de vezes para rodar o algorítmo.
     *
     * @return string
     */
    public function make($stringToHash, $times = null)
    {
        return $this->generateHash($stringToHash);
    }

    /**
     * Verifica se a string equivale ao hash.
     *
     * @param string $stringToCHeck String para comparar.
     * @param string $hash          Hash para comparação.
     *
     * @return bool
     */
    public function needsRehash($hash, $times = null)
    {
        return false;
    }

    /**
     * Verifica se a string necessita ser criptografada novamente.
     *
     * @param string $hash  String para verificar.
     * @param string $times Quantas vezes o hash deveria ter sido rodado.
     *
     * @return bool
     */
    public function verify($stringToCheck, $hash)
    {
        return $this->generateHash($stringToCheck) === $hash;
    }

    /**
     * Cria e retorna a string com o hash gerado da string passada por parâmetro.
     *
     * @param string $senha string para gerar o hash.
     * @param string $times numero de vezes para rodar o algorítmo.
     *
     * @return string
     */
    public function generateHash($senha, $times = null)
    {
        $md5 = md5(strtolower(self::SALT . $senha));

        return base64_encode($md5 ^ md5($senha));
    }
}
