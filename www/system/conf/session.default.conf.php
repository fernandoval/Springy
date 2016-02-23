<?php
/** \file
 *  \brief      Configutarions for Springy\Session class.
 *  \copyright  Copyright (c) 2007-2016 Fernando Val
 *  \warning    Este arquivo é parte integrante do framework e não pode ser omitido
 */

/**
 *  \defgroup sessioncfg Configurações de SESSÃO
 *  \ingroup config.
 *
 *  As entradas de configuração dos arquivos \c session, são utilizadas pela classe Session, sendo que as entradas previamente definidas não podem ser omitidas
 *  sob risco da classe não funcionar.
 *
 *  Você poderá adicionar entradas personalizadas de sua aplicação.
 *
 *  Entradas de configuração:
 *	- \c type - determina o tipo de tratamento de sessão.\n Os seguintes valores são aceitos:\n
 *	  - \c 'std' - tratamento de sessão padrão utilizando session_start();
 *	  - \c 'db' - tratamento de sessão em banco de dados;
 *	  - \c 'memcached' - tratamento de sessão utilizando Memcached (http://www.php.net/manual/en/book.memcached.php)
 *  - \c server_addr - endereço servidor de banco ou memcadhed (quando tipo de controle de sessão não é o padrão)
 *  - \c server_port - porta do servidor de banco ou memcadhed (quando tipo de controle de sessão não é o padrão)
 *  - \c master_domain - domínio master para o cookie de sessão
 *  - \c expires - define o tempo de validade da sessão em minutos
 *  - \c table_name - define o nome da tabela para sessões em banco de dados (quando o tipo de controle for 'db')
 *  - \c id_column - define o nome da coluna do id da sessão (quando o tipo de controle for 'db')
 *  - \c value_column - define o nome da coluna do valor da sessão (quando o tipo de controle for 'db')
 *  - \c update_column - define o nome da coluna da data de atualização da sessão (quando o tipo de controle for 'db')
 *
 *  \see config
 *
 *  @{
 *  @}
 */

/**
 *  \defgroup sessioncfg_default Configurações de SESSÃO para todos os ambientes
 *  \ingroup sessioncfg.
 *
 *  As entradas colocadas nesse arquivo serão aplicadas a todos os ambientes do sistema.
 *
 *  Veja \link dbcfg Configurações de SESSÃO \endlink para entender as entradas de configuração possíveis.
 *
 *  \see sessioncfg
 */
/**@{*/

/// Entradas para todos os ambientes
$conf = [];

/**@}*/
