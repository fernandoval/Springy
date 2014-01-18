<?php
/** \file
 *  \brief Configurações do Mini CMS
 *
 *  \warning Este arquivo é parte integrante do framework e não pode ser omitido
 *
 *	\copyright	Copyright (c) 2007-2014 FVAL Consultoria e Informática Ltda.
 */

/**
 *  \defgroup cmscfg_production Configurações do Mini CMS para o ambiente 'production'
 *  \ingroup cmscfg
 *  
 *  As entradas colocadas nesse arquivo serão aplicadas apenas ao ambiente 'production'.
 *  
 *  Seu sistema pode não possuir esse ambiente, então use-o como modelo para criação do arquivo de
 *  parâmetros de configuração para os ambientes que seu sistema possua.
 *  
 *  Veja \link cmscfg Configurações do Mini CMS \endlink para entender as entradas de configuração possíveis.
 *  
 *  \see cmscfg
 */
/**@{*/

/// Entradas para o ambiente de Produção
$conf = array('articles_per_page' => 10);

/**@}*/