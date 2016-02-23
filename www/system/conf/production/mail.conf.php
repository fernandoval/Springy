<?php
/** \file
 *  \brief      Configurations for Springy\Mail class.
 *  \copyright  Copyright (c) 2007-2016 Fernando Val
 *  \warning    Este arquivo é parte integrante do framework e não pode ser omitido
 */

/**
 *  \defgroup emailcfg_production Configurações da classe de envio de email para o ambiente \c 'production'
 *  \ingroup emailcfg.
 *
 *  As entradas colocadas nesse arquivo serão aplicadas apenas ao ambiente 'production'.
 *
 *  Seu sistema pode não possuir esse ambiente, então use-o como modelo para criação do arquivo de
 *  parâmetros de configuração para os ambientes que seu sistema possua.
 *
 *  Veja \link emailcfg Configurações da classe de envio de email \endlink para entender as entradas de configuração possíveis.
 *
 *  \see emailcfg
 */
/**@{*/

/// Configurações para o ambiente de Produção
$conf = [
    'default_driver' => 'sendgrid-api',
    'mailers'        => [
        'sendgrid-api' => [
            'apikey' => 'put-the-sendgrid-api-key-here',
        ],
    ],
];

/**@}*/
