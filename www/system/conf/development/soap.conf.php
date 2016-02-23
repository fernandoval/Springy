<?php
/** \file
 *  \brief      Configurações da classe SOAP.
 *  \copyright  Copyright (c) 2007-2016 Fernando Val
 *  \warning    Este arquivo é parte integrante do framework e não pode ser omitido
 */

/**
 *  \defgroup soapcfg_development Configurações da classe SOAP para o ambiente 'development'
 *  \ingroup soapcfg.
 *  
 *  As entradas colocadas nesse arquivo serão aplicadas apenas ao ambiente 'development'.
 *  
 *  Seu sistema pode não possuir esse ambiente, então use-o como modelo para criação do arquivo de
 *  parâmetros de configuração para os ambientes que seu sistema possua.
 *  
 *  Veja \link dbcfg Configurações de acesso a banco de dados \endlink para entender as entradas de configuração possíveis.
 *  
 *  \see soapcfg
 **/
/**@{*/

/// Entradas para o ambiente de Desenvolvimento
$conf = [
    'useCURL' => false,
    'timeout' => 15,
];

/**@}*/
