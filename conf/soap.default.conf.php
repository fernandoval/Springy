<?php
/** \file
 *  \brief      Configurações da classe SOAP.
 *  \copyright  Copyright (c) 2007-2016 Fernando Val
 *  \warning    Este arquivo é parte integrante do framework e não pode ser omitido.
 */

/**
 *  \defgroup soapcfg Configurações da classe SOAP
 *  \ingroup config.
 *
 *  As entradas de configuração dos arquivos \c soap, são utilizadas pela classe SOAP, SOAP_Server e SOAP_Client,
 *  sendo que as entradas previamente definidas não podem ser omitidas sob risco dessas classes não funcionarem.
 *
 *  Você poderá adicionar entradas personalizadas de sua aplicação.
 *
 *  Entradas de configuração:
 *  - \c useCURL - determina se o modo CURL deve ser usado
 *  - \c timeout - determina o timeout em segundos
 *
 *  \see config
 *
 *  @{
 *  @}
 **/

/**
 *  \defgroup soapcfg_default Configurações da classe SOAP para todos os ambientes
 *  \ingroup soapcfg.
 *
 *  As entradas colocadas nesse arquivo serão aplicadas a todos os ambientes do sistema.
 *
 *  Veja \link soapcfg Configurações da classe SOAP \endlink para entender as entradas de configuração possíveis.
 *
 *  \see soapcfg
 */
/**@{*/

/// Entradas para todos os ambientes
$conf = [];

/**@}*/
