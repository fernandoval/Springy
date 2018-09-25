<?php
/**
 * General system configutations.
 *
 * Production environment.
 *
 * @copyright 2007 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
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
