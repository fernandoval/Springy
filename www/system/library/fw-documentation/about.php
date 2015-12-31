<?php
/** \mainpage
 *  
 *  ## FVAL PHP Framework
 *  
 *  \version 3.3.1
 *  
 *  http://framework.fval.net.br
 *  
 *  \copyright Copyright (C) 2007-2015 FVAL - Consultoria e Informática Ltda.
 *  \copyright Copyright (C) 2007-2015 Fernando Val
 *  \copyright Copyright (C) 2009-2013 Lucas Cardozo
 *  
 *  \author Fernando Val  - fernando.val@gmail.com
 *  \author Lucas Cardozo - lucas.cardozo@gmail.com
 *  
 *  
 *  ### Documentação ###
 *  
 *  \section about Sobre o framework:
 *  
 *  O FVAL PHP Framework for Web Applications foi projetado para ser um framework de desenvolvimentos de aplicações web em PHP no formato MVC, leve, de fácil aprendizado, rico em recursos, adaptável e de execução ágil.
 *  
 *  Este framework nasceu a partir de um projeto pessoal de Fernando Val, desenvolvido em 2005, para facilitação de desenvolvimento de projetos e sites baseados em PHP. Em 2007 o projeto foi totalmente reescrito e ganhou orientação a objetos, ainda mantendo-se a compabilidade com a versão 4 do PHP.
 *  
 *  No ano de 2008 Lucas Cardozo agregou nova estrutura e recursos ao projeto, transformando-o num micro-framework, utilizado nos projetos da equipe de desenvolvedores PHP liderada por Fernando. Entretanto não havia uma classe com suporte a múltiplas plataformas de sistemas gerenciadores de bancos de dados (SGBD) e apenas o MySQL era suportado.
 *  
 *  O framework foi finalmente compilado, remodelado e reestruturado, transformando-se em plataforma MVC, utilizando a classe Smarty (http://www.smarty.net) como mecanismo de renderização das visões (views), em 2009 por Fernando. O suporte ao PHP 4 foi abandonado e o PHP 5.0 passou a ser menor versão admissível para utilização do framework. A classe de bando de dados adoDB (http://adodb.sourceforge.net) foi adicionada para suporte a múltiplos SGBD.
 *  
 *  Em 2011 a classe adoDB foi abandonada e o mecanismo PDO (PHP Data Objects) foi adotado. Dessa forma a menor versão suportada para utilização do framework passou a ser a 5.1.
 *  
 *  
 *  \section howmvcworks O sistema MVC deste framework:
 *  
 * Os models (modelos) nada mais são que classes para tratamento de dados ou funções genéricas ou de uso comum que não fazem parte do cerne do framework. Nesse framework os models são tratados simplesmento como "classes proprietárias" e ficam armazenadas no diterório de classes proprietárias (ver estrutura de diretórios). O framework possui uma classe de tratamento de bancos da qual você poderá herdar suas classes para tratamento de tabelas em banco, simplesmente crie suas classes extendendo a classe \FW\Model.
 *  
 *  As views (visões), que são o resultado visual do processamento, são tratadas como templates (modelos) e o desenvolvedor pode optar por utiliza as bibliotecas Smarty (http://smarty.net) ou Twig (http://twig.sensiolabs.org) como mecanismos de renderização, sendo que os templates Smarty deverão ter a terminação ".tpl.html" em seus nomes, enquanto para Twig deverão ser terminados por ".twig.html". Os templates são armazenados no diretório de templates que pode ter múltiplos níveis de acordo com a necessidade do projeto.
 *  
 *  As controladoras são os responsáveis pelo processamento específico de cada funcionalidade da aplicação e ficam armazenadas no diretório de controladoras que, assim como o diretório de templates, pode conter múltiplos níveis para atender às necessidades do projeto.
 *  
 *  
 *  \section fwdirstruc A estrurura de diretórios do framework:
 *  
 *  Como todo framework voltado para o desemvolvimento de aplicações para web, o diretório principal deste framework, é o diretório raiz do site que,
 *  no caso do servidor web Apache (http://www.apache.org) é chamado de DOCUMENT_ROOT.
 *  
 *  Dentro do diretório principal, está o script de inicialização e responsável pela carga da controladora adequado, além da localização e carga das classes proprietárias e classes
 *  do framework que venham a ser utilizadas pela aplicação, que chamaremos simplemente de "inicializador". No pacote de distribuição, o inicializador vem denominado _Main.php.
 *  
 *  Existe um arquivo index.php, que normalmente é chamado pelo servidor web, mas este script simplesmente mata a execução do PHP e retorna uma mensagem de acesso inválido.
 *  Isso foi feito pensando que você irá trabalhar com MOD_REWRITE do Apache. Por esse motivo existe um arquivo .htaccess no diretório raiz da aplicação.
 *  
 *  Tão importante quando o inicializador é o script sysconf.php. Este script é o primeiro a ser carregado pelo inicializador e sua função é definir as configurações principais do cerne da aplicação.
 *  
 *  A seguir será explicada a função de cada um dos diretórios do framework e, para isso, será utilizado o nome usado no pacote de distribuição, pois nada impede que a equipe de
 *  desemvolvedores os altere e regonfigure o framework.
 *  
 *  Lista padrão de diretórios do framework:
 *  
 *  \li \b "www" : é o diretório onde está o inicializador e o sysconf.php. Em alguns ambientes essa pasta tem nomes como httpdocs ou public_html;
 *  \li \b "system" : é onde estão os subdiretórios do arquivos do framework e da aplicação. Pos questões de seguramça, recomenda-se que este diretório esteja fora da árvore navegável
 *  da aplicação, se seu servidor web permitir o acesso a arquivos fora da árvore do www. Neste caso é necessário ajustar o caminho no arquivo sysconf.php;
 *  \li \b "library" : diretório onde estão armazenados as classes e bibliotecas do framework;
 *  \li \b "classes" : diretório onde são armazenadas as classes proprietárias;
 *  \li \b "conf" : diretório onde são armazenados os scripts de configuração do sistema;
 *  \li \b "controller" : diretório onde as controladoras são armazenados (pode conter subdiretórios);
 *  \li \b "other" : diretório onde estão armazenados as classes e bibliotecas de terceiros;
 *  \li \b "templates" : diretório onde são asmazenados os templates (pode conter subdiretórios);
 *  \li \b "tpl_comp" : diretório onde são asmazenados os templates compilados (o PHP deve ter direito de escrita dentro dele);
 *  \li \b "tpl_cache" : diretório onde a classe de templates irá salvar as páginas cacheadas (o PHP deve ter direito de escrita dentro dele).
 *  
 *  
 *  \section etectionofcontroller Como o sistema elege um script de controle (controladora):
 *  
 *  Para determinar qual script de controle será carregado e executado, o sistema se baseia na URI recebida. Ele desmembra o URI em segmentos e varre o diretório das controladoras,
 *  começando pelo primeiro segmento do URI, da seguinte forma até que a primeira condição se revele verdadeira:
 *  
 *  1) Há um script PHP de nome igual ao segmento:p, acrescido do sufixo ".page";
 *  
 *  2) Há um subdiretório de nome igual ao segmento e dentro dele há um script denominado "index.page" e não mais segmentos.
 *  
 *  Se nenhuma das condições acima se mostrarem verdadeiras e houver um subdiretório de nome igual ao segmento e houver mais algum segmento, o sistema passa para o próximo segmento e
 *  refaz as verificações acima. Isso repete até que um script seja elegido controladora ou esgotem-se os segmentos do URI.
 *  
 *  Caso o sistema tenha eleito uma controladora, ele o carrega para a memória e a partir daí faz as verificações de exeção da controladora (veja o próximo capítulo).
 *  
 *  Não havendo uma controladora eleita, a página de erro 404 é mostrada.
 *  
 *  
 *  \section precontroller A pré-controladora _global:
 *  
 *  Antes de construir a controladora e fazer a chamada de seus métodos, o framework verifica a existência do script _global.php, no diretório de controladoras. Caso esse arquivo exista,
 *  será carregado e seu método construtor executado. Este script serve como um hook de inicialização que sempre é executado antes de qualquer controladora ser carregada, além de também poder ser usado como biblioteca de classes e funções para todo o sistema.
 *  
 *  Para que a _global possa ser usada como um hook de inicialização, o script deve conter uma classe de nome Global_Controller e seu respectivo método __construct.
 *  
 *  Caso alguma controladora necessite que a pré-controladora _global não seja carregada, acrescente o método estático púbico de nome \b _ignore_global, à mesma. Esse método não será executado,
 *  mas sua existência orientará o framework a não executar a _global.
 *  
 *  
 *  \section defaultcontroller A pré-controladora _default:
 *  
 *  Assim como acontece com a _global.php, o framework verifica a existência do programa _default.php. Só que, diferentemente do _global.php, esse arquivo deve estar no mesmo diretório da controladora a ser executada e, sendo verdadeiro, o carrega para a memória. Da mesma forma que a _global, para que o _default seja usado como hook, se faz necessário que a classe Default_Controller e seu método __construct sejam criados.
 *  
 *  
 *  \section thecontroller A controladora e seus métodos:
 *  
 *  Eleito a controladora, o sistema cria uma classe com seu nome (o script é carregado automaticamente pelo sistema). Consequentemente o PHP irá executar o método construtor da classe,
 *  que poderá ser usado pela equipe de desenvolvimento para inicializar a variáveis ou até mesmo executar a principal ação da controladora, caso este não possua um método para o tratamento
 *  do segmento de URI seguinte (como veremos a seguir).
 *  
 *  Tendo sucesso ao carregar a controladora, o sistema verifica se há segmento de URI seguinte e em caso afirmativo, busca por um método de nome igual a este segmento. Havendo este médoto, o executa.
 *  
 *  Caso a condição anterior não aconteça, o sistema procura pelo método _default e o executa, se existir.
 *  
 *  
 *  \section theend Fim do processamento:
 *  
 *  Após os métodos da controladora terminarem sua execução e devolverem o controle para o inicializador, este faz os tratamentos finais e encerra a aplicação.
 *  
 *  \todo Está previsto para uma versão futura do framework o desenvolvimento de um hook de finalização a ser executado antes do sistema terminar.
 *  
 *  
 *  \section thelib A biblioteca de classes:
 *  
 *  O framework já possuiu uma série de classes que podem ser utilizadas pela equipe de programadores a fim de facilitar e agilizar o desenvolvimento do projeto.
 *  
 *  Para uma listagem completa das classes da biblioteca, acesse a documentação do framework no seguinte endereço: http://framework.fval.net.br
 *  
 *  
 *  \section jslibs Bibliotecas Javascript inclusas no pacote:
 *  
 *  O framework já contém as sequintes bibliotecas e componentes Javascript:
 *  
 *  \li jQuery - http://jquery.com/ - jQuery is a fast and concise JavaScript Library that simplifies HTML document traversing, event handling, animating, and Ajax interactions for rapid web development.
 *  \li TinyMCE - http://tinymce.moxiecode.com/ - TinyMCE is a platform independent web based Javascript HTML WYSIWYG editor control released as Open Source under LGPL by Moxiecode Systems AB.
 *  It has the ability to convert HTML TEXTAREA fields or other HTML elements to editor instances. TinyMCE is very easy to integrate into other Content Management Systems.
 *  \li Bootstrap
 *  
 *  
 *  \section copyright Direitos Autorais e Propriedade Intelectual:
 *  
 *  Este framework utiliza componentes na licença GPL e outras licenças Open Source, entretanto esse framework é distribuído sob a licença MIT.
 *  
 *  A pesar desse framework ser distribuído sob uma licença Open Source, recomendamos a leitura da Lei 9609 de 19/02/1998 para esclarecimentos quanto a direitos autorais http://www.planalto.gov.br/ccivil/Leis/L9609.htm brasileiros.
 *  
 *  Para detalhes de como utilizar esse framework, entre em contato com os autores.
 *  
 *  
 *  \history Histórico:
 *  
 *  Veja o arquivo history.txt contido no diretório de documentação
 *  \verbinclude history.txt
 *  
 *  \namespace  FW
 *  \brief		Biblioteca de classes do framework
 *  
 */
namespace FW;
