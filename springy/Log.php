<?php
/**	\file
 *	Springy.
 *
 *	\brief		Classe para geração de saídas em logs de eventos.
 *  \copyright	Copyright (c) 2007-2016 Fernando Val
 *  \author		Fernando Val  - fernando.val@gmail.com
 *	\warning	Este arquivo é parte integrante do framework e não pode ser omitido
 *	\version	0.11.13
 *	\ingroup	framework
 */

namespace Springy;

use Springy\Utils\Strings;

/**
 *  \brief Classe para geração de saídas em logs de eventos.
 */
class Log
{
    /**
     *	\brief Escreve uma informação no log.
     *
     *	@param[in] $message \c string mensagem a ser gravada no log de erros do PHP
     *	@param[in] $type \c integer informa onde o log deve ser escrito.
     *	@param[in] $destination \c string destino da mensagem. Veja as opções de $type.
     *
     *	Os possívels valores para $type são:\n
     *		0 - messagem enviada para o sisema de log do PHP. Este é o valor padrão.\n
     *		1 - messagem enviada por email para o endereço definido por $destination.\n
     *		2 - Não é uma opção.\n
     *		3 - messagem adicionada ao arquivo definido por $destination. Uma nova linha não é adicionada automaticamente ao final de $message.\n
     *		4 - messagem enviada diretamenteo para o handler de log SAPI.
     */
    public static function write($message, $type = 0, $destination = null)
    {
        /// Pega o IP do usuário
        $source_ip = Strings::getRealRemoteAddr();
        /// Pega a página onde ocorreu o evento
        $url = URI::getURIString();

        //$message = Strings::removeAccentedChars($message);

        /// Monta a linha do evento
        $evt_message = date('Y-m-d H:i:s') . ' ' . $source_ip . ' ' . $url . ' "' . $message . '"' . ($type == 3 ? "\n" : '');

        error_log($evt_message, $type, $destination);
    }
}
