<?php
/** \file
 *  \brief Configurações da classe de envio de email.
 *
 *  \warning Este arquivo é parte integrante do framework e não pode ser omitido
 *
 *  \copyright	Copyright (c) 2007-2015 FVAL Consultoria e Informática Ltda.\n
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
            'driver'        => 'phpmailer',
            'protocol'      => 'smtp',
            'host'          => 'smtp.gmail.com',
            'port'          => 587,
            'cryptography'  => 'tls',
            'authenticated' => true,
            'username'      => 'you@gmail.com',
            'password'      => 'put-your-password-here',
            'debug'         => 0,
        ],
        'sendgrid-api' => [
            'driver'        => 'sendgrid',
            'apikey'        => 'put-the-sendgrid-api-key-here',
            'options'       => [
                'protocol'  => 'https',
                // 'host'      => 'smtp.sendgrid.net',
                // 'endpoint'  => '/api/mail.send.json',
                // 'port'      => null,
                // 'url'       => null,
                'raise_exceptions'          => false,
                'turn_off_ssl_verification' => false,
            ],
        ],
        'sendgrid-smtp' => [
            'driver'        => 'sendgrid',
            'apikey'        => '',
            'username'      => 'put-your-sendgrid-user-here',
            'password'      => 'put-your-sendgrid-pass-here',
            'options'       => [
                'protocol'                  => 'smtp',
                'host'                      => 'smtp.sendgrid.net',
                'port'                      => 465,
                'raise_exceptions'          => false,
                'turn_off_ssl_verification' => false,
            ],
        ],
        'mimemessage' => [
            'driver'          => 'mimemessage',
            'protocol'        => 'default',
            'host'            => 'localhost',
            'port'            => 25,
            'ssl'             => '0',
            'starttls'        => '0',
            'direct_delivery' => '0',
            'exclude_address' => '',
            'username'        => '',
            'password'        => '',
            'workstation'     => '',
            'realm'           => '',
            'auth_host'       => null,
            'debug'           => 0,
            'html_debug'      => 1,
        ],
    ],
];

/**@}*/
