FVAL PHP Framework for Web Applications

Copyright (C) 2007-2013 FVAL - Consultoria e Informática Ltda.
Copyright (C) 2007-2013 Fernando Val
Copyright (C) 2009-2013 Lucas Cardozo

http://www.fval.com.br


Migrando para a Versão 2.0.0 de versões anteriores
--------------------------------------------------

A maior mudança ocorrida da versão 1.4.0 para a versão 2.0.0 é que foi instituído o conceito de namespace no framework e todas as classes da biblioteca passaram a pertencer ao namespamce FW.

Dessa forma, todas as chamadas às classes da biblioteca do framework devem ser precedidas por \FW\.

Exemplo:

$controllerRoot = \FW\Kernel::controllerRoot();


Migrando para a Versão 1.4.0 de versões anteriores
--------------------------------------------------

A principal mudança na versão 1.4.0 foi a alteração dos nomes dos métodos das classes, que foram padronizados em lowerCamelCase. Dessa forma, todos os método contendo underscore (_) entre nas palavras que compunham o nome do método, devem ser alterados para seua novos nomes. Na maioria dos casos, basta remover o underscore e colocar o método no formado lowerCamelCase. Para os métodos que foram renomeados, veja a relação abaixo:

Kernel::get_controller_root()                 ----> Kernel::controllerRoot()
Kernel::set_controller_root($novoValor)       ----> Kernel::controllerRoot($novoValor)
Kernel::get_controller_namespace()            ----> Kernel::controllerNamespace()
Kernel::set_controller_namespace($controller) ----> Kernel::controllerNamespace($controller)
Kernel::get_debug()                           ----> Kernel::getDebugContent()
Kernel::get_conf($local, $var)                ----> Configuration::get($local, $var)
Kernel::set_conf($local, $entry)              ----> Configuration::set($local, $var, $value)
Kernel::load_conf($local)                     ----> Configuration::load($local)
DB->get_inserted_id()                         ----> DB->lastInsertedId()
DB->num_rows()                                ----> DB->affectedRows()
DB->get_all([$res])                           ----> DB->fetchAll([$res])
DB->dateToTime($dateTime)                     ----> DB->makeDbDateTime($dateTime)
DB->dateToStr($dataTimeStamp)                 ----> DB->londBrazilianDate($dataTimeStamp)
Session::get_session_id()                     ----> Session::getId()
Strings::check_email_address($email, $dns)    ----> Strings::validateEmailAddress($email, $dns)
Strings::check_valid_slug($slug)              ----> Strings::validateSlug($slug)
Strings::check_valid_text($text)              ----> Strings::validateText($text)
URI::get_class_controller()                   ----> URI::getControllerClass()
URI::slug_generator($string)                  ----> URI::makeSlug($string)
Session::is_set($var)                         ----> Session::defined($var)
