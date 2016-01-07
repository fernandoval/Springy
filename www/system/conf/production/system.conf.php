<?php
/** \file
 *  \brief Configurações gerais do sistema.
 *
 *  \warning Este arquivo é parte integrante do framework e não pode ser omitido
 *
 *  \copyright	Copyright (c) 2007-2015 FVAL Consultoria e Informática Ltda.\n
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
    'debug'             => false,
    'maintenance'       => false,
    'rewrite_url'       => true,
    'cache'             => false,
    'cache-control'     => 'private, must-revalidate',
    'authentication'    => [],
];

/**@}*/
