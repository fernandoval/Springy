<?php

/**
 * Log.
 *
 * @copyright 2016 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 * @license   https://github.com/fernandoval/Springy/blob/master/LICENSE MIT
 *
 * @version   0.11.14
 *
 * @deprecated 4.5.0
 */

namespace Springy;

use Springy\Utils\Strings;

class Log
{
    /**
     * Escreve uma informação no log.
     *
     * Os possívels valores para $type são:\n
     * 0 - messagem enviada para o sisema de log do PHP. Este é o valor padrão.\n
     * 1 - messagem enviada por email para o endereço definido por $destination.\n
     * 2 - Não é uma opção.\n
     * 3 - messagem adicionada ao arquivo definido por $destination. Uma nova linha não é adicionada automaticamente ao final de $message.\n
     * 4 - messagem enviada diretamenteo para o handler de log SAPI.
     *
     * @param string $message     mensagem a ser gravada no log de erros do PHP.
     * @param int    $type        informa onde o log deve ser escrito.
     * @param string $destination destino da mensagem. Veja as opções de $type.
     *
     * @return void
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
