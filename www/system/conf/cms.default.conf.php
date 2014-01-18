<?php
/** \file
 *  \brief Configurações do Mini CMS
 *
 *  \warning Este arquivo é parte integrante do framework e não pode ser omitido
 *
 *	\copyright	Copyright (c) 2007-2014 FVAL Consultoria e Informática Ltda.
 */

/**
 *  \defgroup cmscfg Configurações do Mini CMS
 *  \ingroup config
 *  
 *  As entradas de configuração dos arquivos \c cms, são utilizadas internamente pela classe Mini CMS e pode ser removido de sua
 *  aplicação, caso você desligue a entrada 'CMS' na configuração geral. Vaja \link config Configurações do sistema \endlink.
 *
 *  Entradas de configuração para o Mini CMS:
 *  \li \c articles_per_page - Número de artigos por página
 *  
 *  \see config
 *  @{
 *  @}
 */

/**
 *  \addtogroup cmscfg_default Configurações do Mini CMS para todos os ambientes
 *  \ingroup cmscfg
 *  
 *  As entradas colocadas nesse arquivo serão aplicadas a todos os ambientes do sistema.
 *  
 *  Veja \link cmscfg Configurações do Mini CMS \endlink para entender as entradas de configuração possíveis.
 *  
 *  \see cmscfg
 */
/**@{*/

/// Entradas para todos os ambientes
$conf = array('articles_per_page' => 10);

/**@}*/