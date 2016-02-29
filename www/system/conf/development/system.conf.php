<?php
/** \file
 *  \brief      General system configutations.
 *  \copyright  Copyright (c) 2007-2016 Fernando Val
 *  \warning    Este arquivo é parte integrante do framework e não pode ser omitido.
 */

/**
 *  \defgroup systemcfg_development Configurações do cerne para o ambiente \c 'development'
 *  \ingroup systemcfg.
 *
 *  As entradas colocadas nesse arquivo serão aplicadas apenas ao ambiente 'development'.
 *
 *  Seu sistema pode não possuir esse ambiente, então use-o como modelo para criação do arquivo de
 *  parâmetros de configuração para os ambientes que seu sistema possua.
 *
 *  Veja \link systemcfg Configurações do cerne \endlink para entender as entradas de configuração possíveis.
 *
 *  \see systemcfg
 */
/**@{*/

/// Configurações para o ambiente de Desenvolvimento
$conf = [
    'debug'             => true,
    'maintenance'       => false,
    'cache'             => false,
    'cache-control'     => 'no-cache',
    'system_error'      => [
        'save_in_database' => false,
    ],
];

/**@}*/
