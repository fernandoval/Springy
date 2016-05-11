<?php
/** \file
 *  \brief      General system configutations.
 *  \copyright  Copyright (c) 2007-2016 Fernando Val
 *  \warning    Este arquivo é parte integrante do framework e não pode ser omitido.
 */

/**
 *  \addtogroup systemcfg_production Configurações do cerne para o ambiente \c 'production'
 *  \ingroup systemcfg.
 *
 *  As entradas colocadas nesse arquivo serão aplicadas apenas ao ambiente 'production'.
 *
 *  Seu sistema pode não possuir esse ambiente, então use-o como modelo para criação do arquivo de
 *  parâmetros de configuração para os ambientes que seu sistema possua.
 *
 *  Veja \link systemcfg Configurações do cerne \endlink para entender as entradas de configuração possíveis.
 *
 *  \see systemcfg
 */
/**@{*/

/// Configurações para o ambiente de Produção
$conf = [
    'maintenance'        => false,
    'bug_authentication' => [
        'user' => 'username',
        'pass' => 'password',
    ],
];

/**@}*/
