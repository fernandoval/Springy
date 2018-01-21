<?php
/** @file
 *  Springy.
 *
 *  @brief      Springy Framework configuration file.
 *
 *  @copyright	Copyright (c) 2007-2018 Fernando Val
 *  @author		Fernando Val  - fernando.val@gmail.com
 *
 *  @warning	This is an important file and required to the good work of the system. Do not delete, move or rename it.
 *
 *  @version	3.4.0.19
 */

/**
 *  \addtogroup config.
 *
 *  O script \c sysconf.php é o arquivo de configuração geral do sistema. Nele estão as entradas de definição do nome e versão do sistema,
 *  ambiente, caminhos dos diretórios do sistema, charset e timezone.
 *
 *  Este arquivo é obrigatório e não pode ser removido ou renomeado.
 *
 *  Definição das entradas da configuração geral do sistema:
 *
 *  \li 'SYSTEM_NAME' - Nome do sistema.
 *  \li 'SYSTEM_VERSION' - Versão do sistema.
 *  \li 'ACTIVE_ENVIRONMENT' - Determina o ambiente ativo. São comumente utilizados 'development' e 'production' como valores dessa chave.
 *    Se for deixada em branco, o framework irá buscar entradas de configuração para o host acessado. Por exemplo: 'www.seusite.com.br'
 *  \li 'ENVIRONMENT_VARIABLE' - Define o nome da variável de ambiente que será usada para definir o ambiente ativo.
 *  \li 'CONSIDER_PORT_NUMBER' - Informa à configuração por host que a porta deve ser considerada como parte do conteúdo.
 *  \li 'ENVIRONMENT_ALIAS' - Array contendo um conjunto \c chave => \c valor, onde \c chave representa um apelido para o ambiente \c valor.
 *    A \c chave pode ser uma expressão regular.
 *  \li 'CMS' - Liga ou desliga o sistema Mini CMS.
 *  \li 'ROOT_PATH' - Diretório root da aplicação.
 *  \li 'SYSTEM_PATH' - Diretório do sistema.
 *  \li 'SPRINGY_PATH' - Diretório da biblioteca do sistema.
 *  \li 'VENDOR_PATH' - Diretório da biblioteca de classes de terceiros.
 *  \li 'CONTROLER_PATH' - Diretório das classes da aplicação.
 *  \li 'CLASS_PATH' - Diretório das classes da aplicação.
 *  \li 'CONFIG_PATH' - Diretório das configurações do sistema.
 *  \li 'CHARSET' - Charset do sistema.
 *  \li 'TIMEZONE' - Timestamp do sistema
 *
 *  As demais configurações da aplicação deverão estar no diretório e sub-diretórios definidos pela entrada \c 'CONFIG_PATH'.
 *
 *  O sistema de configuração irá buscar por arquivos contendo o sufixo \c ".conf.php", dentro dos sub-diretórios do ambiente em que o
 *  sistema estiver sendo executado. Além disso, o arquivo de mesmo nome e sufixo \c ".default.conf.php" também será carregado,
 *  caso exista, no diretório raiz das configurações, independete do ambiente, como sendo entradas de configuração padrão para todos os
 *  ambientes. As entradas padrão serão sobrescritas por entradas específicas do ambiente.
 *
 *  A configuração dos arquivos de configuração é feita pela definição da variável de nome \c $conf que é um array de definições de
 *  configuração.
 *
 *  É possível sobrescrever as configurações para determinados hosts de sua aplicação, utilizando a variável \c $over_conf, que é um array
 *  contendo no primeiro nível de índices o nome do host para o qual se deseja sobrescrever determinada(s) entrada(s) de configuração,
 *  que por sua vez, receberá um array contendo cada entrada de configuração a ser sobrescrita.
 *
 *  Os arquivos pré-distribuídos com o framework são de uso interno das classes e não podem ser renomeados ou omitidos.
 *
 *  Seu sistema poderá possuir arquivos de configuração específicos, bastando obedecer o formato e a estrutura de nomes e diretórios.
 */
/**@{*/

/// General framework configuration
$GLOBALS['SYSTEM'] = [
    'SYSTEM_NAME'       => 'Your system name',
    'SYSTEM_VERSION'    => [1, 0, 0],
    'PROJECT_CODE_NAME' => '',

    'ACTIVE_ENVIRONMENT'   => '',
    'ENVIRONMENT_VARIABLE' => 'SPRINGY_ENVIRONMENT',
    'CONSIDER_PORT_NUMBER' => false,
    'ENVIRONMENT_ALIAS'    => [
        'localhost'             => 'development',
        '127\.0\.0\.1'          => 'development',
        '(www\.)?mydomain\.com' => 'production',
    ],

    'ROOT_PATH'      => realpath(dirname(__FILE__)),
    'SYSTEM_PATH'    => '',
    // Springy library directory
    'SPRINGY_PATH'   => realpath(dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'springy'),
    // Configuration directory
    'CONFIG_PATH'    => realpath(dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'conf'),
    'CONTROLER_PATH' => '',
    'CLASS_PATH'     => '',
    // Migration scripts directory
    'MIGRATION_PATH' => realpath(dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'migration'),
    /// Vendor directory
    'VENDOR_PATH'    => realpath(dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'vendor'),

    'CHARSET'  => 'UTF-8',
    'TIMEZONE' => 'America/Sao_Paulo',
];

/// Diretório do sistema
$GLOBALS['SYSTEM']['SYSTEM_PATH'] = realpath($GLOBALS['SYSTEM']['ROOT_PATH'].DIRECTORY_SEPARATOR.'system');
/// Springy library directory (back compatibility entry)
$GLOBALS['SYSTEM']['LIBRARY_PATH'] = $GLOBALS['SYSTEM']['SPRINGY_PATH'];
/// Vendor directory (back compatibility)
$GLOBALS['SYSTEM']['3RDPARTY_PATH'] = $GLOBALS['SYSTEM']['VENDOR_PATH'];
/// Diretório das controladoras
$GLOBALS['SYSTEM']['CONTROLER_PATH'] = realpath($GLOBALS['SYSTEM']['SYSTEM_PATH'].DIRECTORY_SEPARATOR.'controllers');
/// Diretório das classes da aplicação
$GLOBALS['SYSTEM']['CLASS_PATH'] = realpath($GLOBALS['SYSTEM']['SYSTEM_PATH'].DIRECTORY_SEPARATOR.'classes');

/**@}*/
