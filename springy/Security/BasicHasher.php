<?php

/**
 * Classe para geração básica de hashes.
 *
 * @copyright 2007 Fernando Val
 * @author    Allan Marques <allan.marques@ymail.com>
 * @author    Fernando Val <fernando.val@gmail.com>
 * @license   https://github.com/fernandoval/Springy/blob/master/LICENSE MIT
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
     * @param int    $times        numero de vezes para rodar o algorítmo.
     *
     * @return string
     */
    public function make(string $stringToHash, int $times = 10): string
    {
        return $this->generateHash($stringToHash);
    }

    /**
     * Verifica se a string equivale ao hash.
     *
     * @param string $hash  Hash para comparação.
     * @param int    $times numero de vezes para rodar o algorítmo.
     *
     * @return bool
     */
    public function needsRehash(string $hash, int $times = 10): bool
    {
        return false;
    }

    /**
     * Verifica se a string necessita ser criptografada novamente.
     *
     * @param string $stringToCheck String para verificar.
     * @param string $hash          Hash para comparação.
     *
     * @return bool
     */
    public function verify(string $stringToCheck, string $hash): bool
    {
        return $this->generateHash($stringToCheck) === $hash;
    }

    /**
     * Cria e retorna a string com o hash gerado da string passada por parâmetro.
     *
     * @param string $senha string para gerar o hash.
     * @param int    $times numero de vezes para rodar o algorítmo.
     *
     * @return string
     */
    public function generateHash(string $senha, int $times = 10): string
    {
        $md5 = md5(mb_strtolower(self::SALT . $senha));

        return base64_encode($md5 ^ md5($senha));
    }
}
