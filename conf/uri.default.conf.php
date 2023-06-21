<?php

/**
 * Springy Framework Configuration File.
 *
 * Do not remove this file.
 */

/**
 *  \defgroup uricfg Configurações da classe de tratamento de URI/URL
 *  \ingroup config.
 *
 *  As entradas de configuração dos arquivos \c uri, são utilizadas pela classe URI, sendo que as entradas previamente definidas não podem ser omitidas
 *  sob risco de dano à aplicação.
 *
 *  Você poderá adicionar entradas personalizadas de sua aplicação.
 *
 *  Exemplo de código PHP de como usar a entrada \c 'register_method_set_common_urls':
 *
 *  \code{.php}
 *  $conf = [
 *      'register_method_set_common_urls' => [
 *          'class' => 'Url1',
 *          'method' => 'setCommon',
 *          'static' => true
 *      ], // executa: Url1::setCommon()
 *  ];
 *  $conf = [
 *      'register_method_set_common_urls' => [
 *          'class' => 'Urls',
 *          'method' => 'setCommon',
 *          'static' => false
 *      ], // executa: (new Urls())->setCommon()
 *  ];
 *  $conf = [
 *      'register_method_set_common_urls' => [
 *          'class' => 'Urls',
 *          'static' => false
 *      ], // executa: new Urls()
 *  ];
 *  \endcode
 *
 *  Entradas de configuração:
 *  - 'routes' - Array contendo rotas de controladoras. Útil para utilizar mais de uma URL apontando para mesma controladora.
 *  - 'redirects' - Array de redirecionamentos. Útil para configurar redirecionamentos a acessos que possam causar erro de página não encontrada.
 *  - \c 'prevalidate_controller' - Validação prévia de formatação de URI para determinadas controladoras
 *  - \c 'system_root' - URI da página inicial
 *  - \c 'common_urls' - Essa entrada é um array de URLs comuns da aplicação que irão gerar variáveis de template.\n
 *      Cada entrada do array contém uma variável de template, onde o índice é uma string contendo o nome da variável e o valor
 *      é um array podendo conter de um a todos os parâmetros necessários para construção da URL,
 *      conforme a ordem do método buildURL da classe URI.\n
 *      Logo: cada índice do array conterá a seguinte informação, respectivamente:
 *      índice 0 (zero) => array de segmentos da URL;
 *      índice 1 => array da query string;
 *      índice 2 => boleano para o parâmetro forceRewrite;
 *      índice 3 => entrada de configuração host;
 *      índice 4 => booleano para o parânetro include_ignores_segments.
 *  - 'redirect_last_slash' - Valor boleano que determina se a aplicação deve redirecionar requisições a URIs terminadas em barra (/) para URI semelhante sem a barra no final.
 *  	Útil para evitar conteúdo duplicado em ferramentas SEO.
 *  - 'force_slash_on_index' - 	Força o uso de barra (/) ao final da URL para o primeiro segmento (index).
 *  	Se a URL acessada for da página principal (index) e não houver a barra ao final, faz o redirecionamento para a URL com / ao final.
 *  	Esse parâmetro invalida 'redirect_last_slash' para a página principal.
 *  - 'ignored_segments' - 	Quantidade de segmentos iniciais que devem ser ignorados na descoberta da controladora.
 *  	Útil para sites onde alguns dos segmentos iniciais servem para algum parâmetro a ser passado para a aplicação, como por exemplo para determinar
 *  	idioma, localização ou setor. Por exemplo: http://www.seusite.com/pt-br/pagina
 *  - 'host_controller_path' - Array de hosts que possuem nível próprio de controladoras.
 *  - 'dynamic' - Host do conteúdo dinâmico do site. Ex.: 'http://www.seusite.com'
 *  - 'static' - Host do conteúdo estático do site. Ex.: 'http://cdn.seusite.com'
 *  - 'secure' - Host seguro do site. Ex.: 'https://www.seusite.com'
 *
 *  \see config
 *
 *  @{
 *  @}
 */

/**
 *  \defgroup uricfg_default Configurações da classe de tratamento de URI/URL para todos os ambientes
 *  \ingroup uricfg.
 *
 *  As entradas colocadas nesse arquivo serão aplicadas a todos os ambientes do sistema.
 *
 *  Veja \link uricfg Configurações da classe de tratamento de URI/URL \endlink para entender as entradas de configuração possíveis.
 *
 *  \see uricfg
 */

$conf = [
    /*
     * New routing for PSR-4 controllers
     */
    'routing' => [
        /*
         * Default module for controllers.
         *
         * @var string
         */
        'module' => '',

        /*
         * Default namespace for controllers.
         *
         * @var string
         */
        'namespace' => 'App\\Web\\',

        /*
         * Default namespaces by URI segments.
         *
         * @var array
         */
        'segments' => [
            'api' => 'App\\Api',
        ],

        /*
         * Routing configuration by HTTP host.
         *
         * Keys are regular expressions.
         *
         * @var array
         */
        'hosts' => [
            'localhost\.localdomain' => [
                'module' => 'local',
                'namespace' => 'App\\Local\\Web',
                'segments' => [
                    'api' => 'App\\Local\\Api',
                ],
                'template' => ['$admin'],
            ],
            // Command line controllers
            'cmd\.shell' => [
                'module' => '',
                'namespace' => 'App\\Console',
                'segments' => [],
                'template' => [],
            ],
        ],

        /*
         * Page routing.
         *
         * @var array
         */
        'routes' => [
            'App\\Web\\' => [
                'end-of-user-license-agreement' => 'Eula',
            ],
        ],
    ],

    /*
     * Old style regex routing.
     */
    'routes' => [
        'home(\/)*(\?(.*))*' => [
            'segment' => 0,
            'controller' => 'index'
        ],
    ],
    /*
     * Old style redirections.
     */
    'redirects' => [
        '404' => [
            'segments' => [],
            'get' => [],
            'force_rewrite' => false,
            'host' => 'dynamic',
            'type' => 301,
        ],
    ],
    /*
     * Old style controller rounting.
     */
    'prevalidate_controller' => [
        // 'mycontroller'      => ['command' => 301, 'segments' => 2],
        // 'myothercontroller' => ['command' => 404, 'segments' => 2, 'validate' => ['/^[a-z0-9\-]+$/', '/^[0-9]+$/']],
    ],
    /*
     * Old style controller path by host.
     */
    'host_controller_path' => [
        // 'cmd.shell' => ['$command'],
    ],
    'system_root' => '/',
    'register_method_set_common_urls' => null,
    // URLs comuns do site
    'common_urls'                     => [
        'urlAssets' => [['assets'], [], false, 'static', true],
        'urlHome'   => [[]],
        'urlLogin'  => [['login'], [], false, 'secure', true],
        'urlLogout' => [['logout'], [], false, 'secure', true],
    ],
    'redirect_last_slash' => true,
    'force_slash_on_index' => true,
    'ignored_segments' => 0,
    'assets_dir' => 'assets',
];
