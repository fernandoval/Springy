<?php
/** \file
 *  FVAL PHP Framework for Web Applications\n
 *  Copyright (c) 2007-2011 FVAL Consultoria e Informática Ltda.
 *
 *  \warning Este arquivo é parte integrante do framework e não pode ser omitido
 *
 *  \version 1.0.0
 *
 *  \brief Configurações do Mini CMS
 */

/**
 *  \addtogroup config Configurações do sistema
 **/
/*@{*/
	/**
	 *  \addtogroup cmscfg Configurações do Mini CMS
	 **/
	/*@{*/

		/**
		 *  \addtogroup cmscfgdevelopment Configurações para o ambiente de Desenvolvimento
		 **/
		/*@{*/
			/// Define a quantidade de artigos por página
			$conf['development']['articles_per_page'] = 10;
		/*@}*/

		/**
		 *  \addtogroup cmscfgproduction Configurações para o ambiente de Produção
		 **/
		/*@{*/
			/// Define a quantidade de artigos por página
			$conf['production']['articles_per_page'] = 10;
		/*@}*/

	/*@}*/
/*@}*/
