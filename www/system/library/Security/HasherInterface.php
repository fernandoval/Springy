<?php
/**	\file
 *	FVAL PHP Framework for Web Applications
 *
 *  \copyright	Copyright (c) 2007-2015 FVAL Consultoria e Informática Ltda.\n
 *  \copyright	Copyright (c) 2007-2015 Fernando Val\n
 *	\copyright Copyright (c) 2014 Allan Marques
 *
 *	\brief		Interface para padronizar os geradores de hashes
 *	\warning	Este arquivo é parte integrante do framework e não pode ser omitido
 *	\version	0.1
 *  \author		Allan Marques - allan.marques@ymail.com
 *	\ingroup	framework
 */
namespace FW\Security;

/**
 * \brief Interface para padronizar o gerador de hashes
 */
interface HasherInterface 
{
    /**
     * \brief Cria e retorna a string com o hash gerado da string passada por parâmetro
     * \param [in] (string) $stringToHash - string para gerar o hash
     * \param [in] (string) $times - numero de vezes para rodar o algorítmo
     * \return (string)
     */
    public function make($stringToHash, $times);    
    
    /**
     * \brief Verifica se a string equivale ao hash
     * \param [in] (string) $stringToCHeck - String para comparar
     * \param [in] (string) $hash - Hash para comparação
     * \return (bool)
     */
    public function verify($stringToCheck, $hash);    
    
    /**
     * \brief Verifica se a string necessita ser criptografada novamente
     * \param [in] (string) $hash - String para verificar
     * \param [in] (string) $times - Quantas vezes o hash deveria ter sido rodado
     * \return (bool)
     */
    public function needsRehash($hash, $times);
}
