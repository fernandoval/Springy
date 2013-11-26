<?php
/** \file
 *  \brief Configurações do Mini CMS
 *
 *  \warning Este arquivo é parte integrante do framework e não pode ser omitido
 *
 *	\copyright	Copyright (c) 2007-2013 FVAL Consultoria e Informática Ltda.
 *  \addtogroup	config
 */
/**@{*/

/**
 *  \addtogroup cmscfg Configurações do Mini CMS
 *
 *  Entradas de configuração para o Mini CMS
 *  \li \c articles_per_page - Número de artigos por página
 */
/**@{*/

/// Entradas para todos os ambientes
$conf['default']['articles_per_page'] = 10;

/// Entradas para o ambiente de Desenvolvimento
$conf['development']['articles_per_page'] = 10;

/// Entradas para o ambiente de Produção
$conf['production']['articles_per_page'] = 10;

/**@}*/
/**@}*/
