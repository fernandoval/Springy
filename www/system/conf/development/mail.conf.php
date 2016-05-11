<?php
/** \file
 *  \brief      Configurations for Springy\Mail class.
 *  \copyright  Copyright (c) 2007-2016 Fernando Val
 *  \warning    Este arquivo é parte integrante do framework e não pode ser omitido.
 */

/**
 *  \defgroup emailcfg_development Configurações da classe de envio de email para o ambiente \c 'development'
 *  \ingroup emailcfg.
 *
 *  As entradas colocadas nesse arquivo serão aplicadas apenas ao ambiente 'development'.
 *
 *  Seu sistema pode não possuir esse ambiente, então use-o como modelo para criação do arquivo de
 *  parâmetros de configuração para os ambientes que seu sistema possua.
 *
 *  Veja \link emailcfg Configurações da classe de envio de email \endlink para entender as entradas de configuração possíveis.
 *
 *  \see emailcfg
 */
/**@{*/

/// Configurações para o ambiente de Desenvolvimento
$conf = [
    'default_driver' => 'phpmailer-class',
    'mailers'        => [
        'phpmailer-class' => [
            'username' => 'you@gmail.com',
            'password' => 'put-your-password-here',
            'debug'    => 0,
        ],
    ],
];

/**@}*/
