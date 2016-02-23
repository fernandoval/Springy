<?php
/** \file
 *  \brief Configurações de sessão.
 *
 *  \warning Este arquivo é parte integrante do framework e não pode ser omitido
 *
 *  \copyright	Copyright (c) 2007-2016 FVAL Consultoria e Informática Ltda.\n
 */

/**
 *  \defgroup sessioncfg_development Configurações de SESSÃO para o ambiente 'development'
 *  \ingroup sessioncfg.
 *  
 *  As entradas colocadas nesse arquivo serão aplicadas apenas ao ambiente 'development'.
 *  
 *  Seu sistema pode não possuir esse ambiente, então use-o como modelo para criação do arquivo de
 *  parâmetros de configuração para os ambientes que seu sistema possua.
 *  
 *  Veja \link sessioncfg Configurações de SESSÃO \endlink para entender as entradas de configuração possíveis.
 *  
 *  \see sessioncfg
 */
/**@{*/

/// Configurações para o ambiente de Desenvolvimento
$conf = [
    'type'          => 'std',
    'server_addr'   => '127.0.0.1',
    'server_port'   => 11211,
    'master_domain' => '',
    'expires'       => 120,
    'table_name'    => 'sessao_usuario',
    'id_column'     => 'id_sessao',
    'value_column'  => 'valor',
    'update_column' => 'data_atualizacao',
];

/**@}*/
