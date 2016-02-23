<?php
/** \file
 *  Springy.
 *
 *  \brief      Classe para tratamento de erros.
 *  \copyright  Copyright (c) 2007-2016 Fernando Val
 *  \author     Fernando Val  - fernando.val@gmail.com
 *  \author     Lucas Cardozo - lucas.cardozo@gmail.com
 *  \warning    Este arquivo é parte integrante do framework e não pode ser omitido
 *  \version    2.2.35
 *  \ingroup    framework
 */
namespace Springy;

use Springy\Utils\Strings;

/**
 *  \brief Classe para tratamento de erros.
 *
 *  Esta classe é estática e invocada automaticamente pelo framework.
 */
class Errors
{
    /// List of ignores errors
    private static $disregarded = [];
    /// List of hooks for errors
    private static $hooks = [];

    /**
     *  \brief Add a error code to the list of disregarded error.
     *
     *  \param (int|array)$error - a error code os a array of errors code
     */
    public static function disregard($error)
    {
        if (is_array($error)) {
            foreach ($error as $errno) {
                self::disregard($errno);
            }
        } else {
            if (!in_array($error, self::$disregarded)) {
                self::$disregarded[] = $error;
            }
        }
    }

    /**
     *  \brief Remove a error code from the list of disregarded error.
     *
     *  \param (int|array)$error - a error code os a array of errors code
     */
    public static function regard($error)
    {
        if (is_array($error)) {
            foreach ($error as $errno) {
                self::regard($errno);
            }
        } else {
            if (isset(self::$disregarded[$error])) {
                unset(self::$disregarded[$error]);
            }
        }
    }

    public static function ajax($errorType, $msg = '')
    {
        self::errorHandler(E_USER_ERROR, $msg, '', '', '', $errorType);
    }

    /**
     *	\brief Encerra o processamento e dá saída na página de erro HTML.
     */
    public static function displayError($errorType, $msg = '')
    {
        $debug = debug_backtrace();
        self::errorHandler(E_USER_ERROR, $msg, $debug[0]['file'], $debug[0]['line'], '', $errorType);
    }

    /**
     *	\brief Trata um erro ocorrido no sistema e encerra seu funcionamento.
     */
    public static function errorHandler($errno, $errstr, $errfile, $errline, $localErro, $errorType = 500)
    {
        if (in_array($errno, self::$disregarded)) {
            return;
        }

        DB::rollBackAll();

        switch ($errno) {
            case E_ERROR:
                $printError = 'Error';
            break;
            case E_WARNING:
                $printError = 'Warning';
                $aFile = explode(DIRECTORY_SEPARATOR, $errfile);
                if (in_array('smarty', $aFile) || strpos($errstr, 'filemtime') !== false || strpos($errstr, 'unlink') !== false) {
                    return;
                }
                $tplPash = Configuration::get('template', 'compiled_template_path');
                if (substr($errfile, 0, strlen($tplPash)) == $tplPash) {
                    return;
                }
            break;
            case E_PARSE:
                $printError = 'Parse Error';
            break;
            case E_NOTICE:
                $printError = 'Notice';
                $tplPash = Configuration::get('template', 'compiled_template_path');
                if (substr($errfile, 0, strlen($tplPash)) == $tplPash) {
                    return;
                }
            break;
            case E_CORE_ERROR:
                $printError = 'Core Error';
            break;
            case E_CORE_WARNING:
                $printError = 'Core Warning';
            break;
            case E_COMPILE_ERROR:
                $printError = 'Compile Error';
            break;
            case E_COMPILE_WARNING:
                $printError = 'Compile Warning';
            break;
            case E_USER_ERROR:
                $printError = 'User Error';
            break;
            case E_USER_WARNING:
                $printError = 'User Warning';
            break;
            case E_USER_NOTICE:
                $printError = 'User Notice';
            break;
            case E_STRICT:
                $printError = 'Fatal Error';
            break;
            case 1044:
                $printError = 'Access Denied to Database';
            break;
            case E_DEPRECATED:
            case E_USER_DEPRECATED:
                $printError = 'Deprecated Function';
                if (Configuration::get('system', 'ignore_deprecated')) {
                    return false;
                }
            break;
            case E_RECOVERABLE_ERROR:
                $printError = 'Fatal Error';
            break;
            default:
                $printError = 'Unknown Error ('.$errno.')';
            break;
        }

        if (PHP_SAPI === 'cli' || defined('STDIN')) {
            self::sendReport(
                $printError.($errstr ? ' - '.$errstr : '').($errfile ? ' in '.$errfile.' on line '.$errline : ''),
                $errorType,
                hash('crc32', $errno.$errfile.$errline) /// error id
            );
        }

        self::sendReport(
            '<span style="color:#FF0000">'.$printError.'</span>'.($errstr ? ': <em>'.$errstr.'</em>' : '').($errfile ? ' in <strong>'.$errfile.'</strong> on line <strong>'.$errline.'</strong>' : ''),
            $errorType,
            hash('crc32', $errno.$errfile.$errline) /// error id
        );
    }

    /**
     *	\brief Monta a mensagem de erro com dados de backtrace, se aplicável.
     */
    public static function sendReport($msg, $errorType, $errorId, $additionalInfo = '')
    {
        $getParams = [];
        foreach (URI::getParams() as $var => $value) {
            $getParams[] = $var.'='.(is_array($value) ? json_encode($value) : $value);
        }

        // Verify if this type of error is reported
        if ($reportedErrors = Configuration::get('system', 'system_error.reported_errors')) {
            if (!is_array($reportedErrors)) {
                $reportedErrors = explode(',', $reportedErrors);
            }
            $sendReport = in_array($errorType, $reportedErrors);
        } else {
            $sendReport = true;
        }
        unset($reportedErrors);

        // Mount error output information
        restore_error_handler();
        try {
            $uname = php_uname('n');
        } catch (Exception $e) {
            $uname = $_SERVER['HOST_NAME'];
        }

        if (PHP_SAPI === 'cli' || defined('STDIN')) {
            $out = 'Error Description: '.$msg."\n"
                 .'Error ID: '.$errorId.' ('.URI::buildURL(['_system_bug_solved_', $errorId]).')'."\n"
                 .'Execution time: '.Kernel::runTime().' seconds'."\n"
                 .'System: '.$uname."\n"
                 .'Secure mode: '.(ini_get('safe_mode') ? 'Yes' : 'No')."\n"
                 .'Date: '.date('Y-m-d')."\n"
                 .'Time: '.date('G:i:s')."\n"
                 .'Request: '.(isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : 'indefinido')."\n"
                 .'Request Method: '.(isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'indefinido')."\n"
                 .'Server Protocol: '.(isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'indefinido')."\n"
                 .'URL: '.URI::getURIString().'?'.implode('&', $getParams)."\n";
                 // . 'Debug: ' . parent::get_debug() . "\n\n"
                 // . 'Info: ' . print_r(parent::make_debug_backtrace(), true) . "\n\n";
        } else {
            $errorTemplate = dirname(realpath(__FILE__)).DIRECTORY_SEPARATOR.'error_template.html';
            if (file_exists($errorTemplate) && $out = file_get_contents($errorTemplate)) {
                $out = self::_parseTemplate($out, $errorId, $msg, $additionalInfo);
            } else {
                $out = '<table class="table table-bordered" width="100%" border="0" cellspacing="0" cellpadding="0">'
                     .'  <tr>'
                     .'    <td style="background-color:#66C; color:#FFF; font-weight:bold; padding-left:10px; padding:3px 2px">Error Description</td>'
                     .'  </tr>'
                     .'  <tr>'
                     .'    <td style="padding:3px 2px">'.$msg.'</td>'
                     .'  </tr>'
                     .'  <tr>'
                     .'    <td style="padding:3px 2px"><strong>Error ID:</strong> '.$errorId.' (<a href="'.URI::buildURL(['_system_bug_solved_', $errorId]).'">was solved</a>)</td>'
                     .'  </tr>'
                     // . '  <tr style="color:#000; display:none" class="bugList">'
                     // . '    <td style="padding:3px 2px"><strong>Número de ocorrências:</strong> [n_ocorrencias] | <strong>Última ocorrência:</strong> [ultima_ocorrencia] (<a href="javascript:;">mais informações</a>)</td>'
                     // . '  </tr>'
                     .'  <tr class="hideMoreInfo">'
                     .'    <td colspan="2">'
                     .'      <table width="100%" border="0" cellspacing="0" cellpadding="0" style="font-family:Arial, Helvetica, sans-serif; font-size:12px">'
                     .'        <tr>'
                     .'          <td colspan="2" style="background-color:#66C; color:#FFF; font-weight:bold; padding-left:10px; padding:3px 2px">Debug Information</td>'
                     .'        </tr>'
                     .'        <tr style="background:#efefef">'
                     .'          <td style="padding:3px 2px"><label style="font-weight:bold">Execution time:</label></td>'
                     .'          <td style="padding:3px 2px">'.Kernel::runTime().' seconds</td>'
                     .'        </tr>'
                     .'        <tr>'
                     .'          <td style="padding:3px 2px"><label style="font-weight:bold">System:</label></td>'
                     .'          <td style="padding:3px 2px">'.$uname.'</td>'
                     .'        </tr>'
                     .'        <tr style="background:#efefef">'
                     .'          <td style="padding:3px 2px"><label style="font-weight:bold">HTTPS:</label></td>'
                     .'          <td style="padding:3px 2px">'.(ini_get('safe_mode') ? 'Yes' : 'No').'</td>'
                     .'        </tr>'
                     .'        <tr>'
                     .'          <td style="padding:3px 2px"><label style="font-weight:bold">Date:</label></td>'
                     .'          <td style="padding:3px 2px">'.date('Y-m-d').'</td>'
                     .'        </tr>'
                     .'        <tr style="background:#efefef">'
                     .'          <td style="padding:3px 2px"><label style="font-weight:bold">Time:</label></td>'
                     .'          <td style="padding:3px 2px">'.date('G:i:s').'</td>'
                     .'        </tr>'
                     .'        <tr>'
                     .'          <td style="padding:3px 2px"><label style="font-weight:bold">Request:</label></td>'
                     .'          <td style="padding:3px 2px">'.$_SERVER['REQUEST_URI'].'</td>'
                     .'        </tr>'
                     .'        <tr style="background:#efefef">'
                     .'          <td style="padding:3px 2px"><label style="font-weight:bold">Request Method:</label></td>'
                     .'          <td style="padding:3px 2px">'.$_SERVER['REQUEST_METHOD'].'</td>'
                     .'        </tr>'
                     .'        <tr>'
                     .'          <td style="padding:3px 2px"><label style="font-weight:bold">Server Protocol:</label></td>'
                     .'          <td style="padding:3px 2px">'.$_SERVER['SERVER_PROTOCOL'].'</td>'
                     .'        </tr>'
                     .'        <tr style="background:#efefef">'
                     .'          <td style="padding:3px 2px"><label style="font-weight:bold">URL:</label></td>'
                     .'          <td style="padding:3px 2px">'.URI::getURIString().'?'.implode('&', $getParams).'</td>'
                     .'        </tr>'
                     .'        <tr>'
                     .'          <td valign="top" style="padding:3px 2px"><label style="font-weight:bold">Debug:</label></td>'
                     .'          <td style="padding:3px 2px"><table width="100%"><tr><td style="font-family:Arial, Helvetica, sans-serif; font-size:12px; padding:3px 2px">'.Kernel::getDebugContent().'</td></tr></table></td>'
                     .'        </tr>'
                     .'        <tr style="background:#efefef">'
                     .'          <td valign="top" style="padding:3px 2px"><label style="font-weight:bold">Info:</label></td>'
                     .'          <td style="padding:3px 2px"><table width="100%"><tr><td style="padding:3px 2px">'.Kernel::makeDebugBacktrace().'</td></tr></table></td>'
                     .'        </tr>'
                     .'        <tr>'
                     .'          <td colspan="2" style="background-color:#66C; color:#FFF; font-weight:bold; padding-left:10px; padding:3px 2px">Client Information</td>'
                     .'        </tr>'
                     .'        <tr>'
                     .'          <td style="padding:3px 2px"><label style="font-weight:bold">HTTP Referer:</label></td>'
                     .'          <td style="padding:3px 2px">'.(isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '').'</td>'
                     .'        </tr>'
                     .'        <tr style="background:#efefef">'
                     .'          <td style="padding:3px 2px"><label style="font-weight:bold">Client IP:</label></td>'
                     .'          <td style="padding:3px 2px">'.Strings::getRealRemoteAddr().'</td>'
                     .'        </tr>'
                     .'        <tr style="background:#efefef">'
                     .'          <td style="padding:3px 2px"><label style="font-weight:bold">Reverse:</label></td>'
                     .'          <td style="padding:3px 2px">'.(Strings::getRealRemoteAddr() ? gethostbyaddr(Strings::getRealRemoteAddr()) : 'no IP').'</td>'
                     .'        </tr>'
                     .'        <tr>'
                     .'          <td style="padding:3px 2px"><label style="font-weight:bold">User Agent:</label></td>'
                     .'          <td style="padding:3px 2px">'.(isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '').'</td>'
                     .'        </tr>'
                     .$additionalInfo
                     .'        <tr>'
                     .'          <td colspan="2" style="background-color:#66C; color:#FFF; font-weight:bold; padding-left:10px; padding:3px 2px">PHP vars</td>'
                     .'        </tr>'
                     .'        <tr>'
                     .'          <td valign="top" style="padding:3px 2px"><label style="font-weight:bold">_POST</label></td>'
                     .'          <td style="padding:3px 2px">'.Kernel::print_rc($_POST).'</td>'
                     .'        </tr>'
                     .'        <tr>'
                     .'          <td valign="top" style="padding:3px 2px"><label style="font-weight:bold">_GET</label></td>'
                     .'          <td style="padding:3px 2px">'.Kernel::print_rc($_GET).'</td>'
                     .'        </tr>'
                     .'        <tr>'
                     .'          <td valign="top" style="padding:3px 2px"><label style="font-weight:bold">_COOKIE</label></td>'
                     .'          <td style="padding:3px 2px">'.Kernel::print_rc($_COOKIE).'</td>'
                     .'        </tr>'
                     .'        <tr>'
                     .'          <td valign="top" style="padding:3px 2px"><label style="font-weight:bold">_SESSION</label></td>'
                     .'          <td style="padding:3px 2px">'.Kernel::print_rc(Session::getAll()).'</td>'
                     .'        </tr>'
                     .'      </table>'
                     .'    </td>'
                     .'  </tr>'
                     .'</table>';
            }
        }

        // Report this error to system admin?
        if ($sendReport) {
            // Sava the error information in database?
            if (Configuration::get('system', 'system_error.save_in_database')) {
                if (!$conn = Configuration::get('system', 'system_error.db_server')) {
                    $conn = 'default';
                }
                if (!$table = Configuration::get('system', 'system_error.table_name')) {
                    $table = 'system_errors';
                }

                $db = new DB($conn);
                if (DB::connected()) {
                    $res = false;
                    $db->errorReportStatus(false);
                    if (!$db->execute('SELECT id FROM '.$table.' WHERE error_code = ?', [$errorId])) {
                        if ($db->statmentErrorCode()) {
                            if (Configuration::get('system', 'system_error.create_table') && $sql = file_get_contents(dirname(__FILE__).DIRECTORY_SEPARATOR.'system_errors_create_table.sql')) {
                                $db->execute($sql);
                            }
                        }
                    } else {
                        $res = $db->fetchNext();
                    }

                    if ($res) {
                        $repeated = true;
                        $db->execute('UPDATE '.$table.' SET occurrences = occurrences + 1 WHERE error_code = ?', [$errorId]);
                    } else {
                        $db->execute('INSERT INTO '.$table.' (error_code, description, details, occurrences) VALUES (?, ?, ?, 1)', [$errorId, $msg, $out]);
                    }
                }
                unset($db, $table, $conn);
            }

            // Send mail message to the system administrator
            if (Configuration::get('mail', 'errors_go_to')) {
                if (!isset($repeated)) {
                    $errorTemplate = dirname(realpath(__FILE__)).DIRECTORY_SEPARATOR.'error_mail_template.html';
                    if (file_exists($errorTemplate) && $errorMail = file_get_contents($errorTemplate)) {
                        $errorMail = self::_parseTemplate($errorMail, $errorId, $msg, $additionalInfo);
                    } else {
                        $errorMail = preg_replace('/\<a href="javascript\:\;" onClick="var obj=\$\(\#(.*?)\)\.toggle\(\)" style="color:#06c; margin:3px 0"\>arguments passed to function\<\/a\>/', '<span style="font-weight:bold; color:#06c; margin:3px 0">Functions arguments:</span>', $out);
                        $errorMail = preg_replace('/ style="display:none"/', '', $errorMail);
                    }

                    $email = new Mail();
                    $email->to(Configuration::get('mail', 'errors_go_to'), 'System Admin');
                    $email->from(Configuration::get('mail', 'system_adm_mail'), Kernel::systemName().' - System Error Report');
                    $email->subject(
                        'Error on '.Kernel::systemName().
                        ' v'.Kernel::systemVersion().
                        ' ['.Kernel::environment().
                        '] at '.URI::http_host()
                    );
                    $email->body($errorMail);
                    $email->send();
                    unset($email);
                }
            }
        }

        if (isset(self::$hooks[$errorType])) {
            $hook = self::$hooks[$errorType];
        } elseif (isset(self::$hooks['default'])) {
            $hook = self::$hooks['default'];
        } elseif (isset(self::$hooks['all'])) {
            $hook = self::$hooks['all'];
        } elseif (!$hook = Configuration::get('system', 'system_error.hook.'.$errorType)) {
            $hook = Configuration::get('system', 'system_error.hook.default');
        }
        if ($hook) {
            if (is_array($hook) && method_exists($hook[0], $hook[1])) {
                $hook[0]->$hook[1]($msg, $errorType, $errorId, $additionalInfo);
            } elseif (function_exists($hook)) {
                $hook($msg, $errorType, $errorId, $additionalInfo);
            }
        }

        self::printHtml($errorType, $out);
    }

    public static function setHook($error, $hook)
    {
        self::$hooks[$error] = $hook;
    }

    /**
     *	\brief Marga um bug como resolvido.
     */
    public static function bugSolved($errorId)
    {
        if (!$conn = Configuration::get('system', 'system_error.db_server')) {
            $conn = 'default';
        }
        if (!$table = Configuration::get('system', 'system_error.table_name')) {
            $table = 'system_errors';
        }

        $db = new DB($conn);
        if (DB::connected()) {
            $db->execute('DELETE FROM '.$table.' WHERE error_code = ?', [$errorId]);
        }
        unset($db);
        die('ID <strong>'.URI::getSegment(1, false).'</strong> deleted from error log.');
    }

    /**
     *	\brief Lista os bugs registrados.
     */
    public static function bugList()
    {
        if (!$conn = Configuration::get('system', 'system_error.db_server')) {
            $conn = 'default';
        }
        if (!$table = Configuration::get('system', 'system_error.table_name')) {
            $table = 'system_errors';
        }

        $db = new DB($conn);
        $order_column = URI::getParam('orderBy') ?: 'last_time';
        $order_type = URI::getParam('sort') ?: 'DESC';
        $db->execute(
            'SELECT id, error_code, description, occurrences, last_time, details'.
            '  FROM '.$table.
            ' ORDER BY '.$order_column.' '.$order_type
        );

        echo
            '<!DOCTYPE html>',
            '<html>',
                '<head>',
                    '<meta charset="UTF-8">',
                    '<title>', Kernel::systemName(), ' version ', Kernel::systemVersion(), ' - Occurrence Errors</title>',

                    '<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.2/css/bootstrap.min.css">',

                    '<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>',
                    '<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.2/js/bootstrap.min.js"></script>',
                '</head>',
            '<body>',
                '<div class="page-header"><h1 class="text-center">', Kernel::systemName(), ' <small>System Error Occurrences</small></h1></div>',

                '<div class="container">',
                    '<div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true">';

        while ($res = $db->fetchNext()) {
            echo
                '<div class="panel panel-default">',
                    '<div class="panel-heading" role="tab" id="heading', $res['id'], '">',
                        '<a data-toggle="collapse" data-parent="#accordion" href="#collapse', $res['id'], '" aria-expanded="true" aria-controls="collapse', $res['id'], '">',
                            '<span class="label label-default">', $res['error_code'], '</span> ', $res['description'], ' <span class="badge">', $res['occurrences'], '</span>',
                        '</a>',
                            '<a class="btn btn-xs btn-primary pull-right" href="', URI::buildURL(['_system_bug_solved_', $res['error_code']]), '">',
                                'DELETE',
                            '</a>',
                    '</div>',
                    '<div id="collapse'.$res['id'].'" class="panel-collapse collapse" role="tabpanel" aria-labelledby="heading'.$res['id'].'">',
                        '<div class="panel-body">';

            $res['details'] = preg_replace('/<table([a-zA-Z0-9"=\%:,; \-]*)>/', '<table class="table table-bordered">', $res['details']);
            $res['details'] = preg_replace('/<tr style="color:#000; display:none" class="bugList">/', '<tr class="bugList">', $res['details']);
            $res['details'] = preg_replace('/(.*)<tr class="hideMoreInfo">/', '<tr class="hideMoreInfo">', $res['details']);
            $res['details'] = preg_replace('/(.*)<!-- CONTENT -->/s', '', $res['details']);
            $res['details'] = str_replace('[last_time]', $res['last_time'], $res['details']);

            echo $res['details'];

            echo '</div></div></div>';
        }

        echo '</div></div></body></html>';

        // die;
    }

    /**
     *	\brief Imprime a mensagem de erro.
     */
    private static function printHtml($errorType, $msg)
    {
        // Verifica se a saída do erro não é em ajax ou json
        //if (!Configuration::get('system', 'ajax') || !in_array('Content-type: application/json; charset=' . Kernel::charset(), headers_list())) {
        if (!URI::isAjaxRequest()) {
            if (ob_get_contents()) {
                ob_clean();
            }

            if (PHP_SAPI === 'cli' || defined('STDIN')) {
                echo $msg."\n";
            } else {
                header('Content-type: text/html; charset=UTF-8', true, $errorType);

                $tplName = Configuration::get('template', 'errors.'.$errorType);
                $tpl = new Template();
                if (!$tplName) {
                    $tplName = '_error'.$errorType;
                }
                if ($tpl->templateExists($tplName)) {
                    $tpl->setTemplate($tplName);

                    $tpl->assign('urlJS', URI::buildURL([Configuration::get('uri', 'js_dir')], [], isset($_SERVER['HTTPS']), 'static'));
                    $tpl->assign('urlCSS', URI::buildURL([Configuration::get('uri', 'css_dir')], [], isset($_SERVER['HTTPS']), 'static'));
                    $tpl->assign('urlIMG', URI::buildURL([Configuration::get('uri', 'images_dir')], [], isset($_SERVER['HTTPS']), 'static'));
                    $tpl->assign('urlSWF', URI::buildURL([Configuration::get('uri', 'swf_dir')], [], isset($_SERVER['HTTPS']), 'static'));

                    $tpl->assign('errorDebug', (Configuration::get('system', 'debug') ? $msg : ''));

                    $tpl->display();
                } else {
                    echo '<!DOCTYPE html>';
                    echo '<html lang="en">';
                    echo '	<head>';
                    echo '		<meta charset="utf-8">';
                    echo '		<meta http-equiv="X-UA-Compatible" content="IE=edge">';
                    echo '		<meta name="viewport" content="width=device-width, initial-scale=1">';
                    echo '		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
                    echo '		<title>'.Kernel::systemName().' ('.Kernel::systemVersion().')</title>';
                    echo '		<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.2/css/bootstrap.min.css">';
                    echo '		<!--[if lt IE 9]>';
                    echo '			<script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>';
                    echo '			<script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>';
                    echo '		<![endif]-->';
                    echo '	</head>';
                    echo '	<body>';
                    echo '		<h1 class="text-center">Error '.$errorType.'</h1>';
                    if (Configuration::get('system', 'debug')) {
                        echo '		<div class="container">';
                        echo '			'.$msg;
                        echo '		</div>';
                    }
                    echo '		<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js"></script>';
                    echo '		<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.2/js/bootstrap.min.js"></script>';
                    echo '	</body>';
                    echo '</html>';
                }
                unset($tpl, $tplName);
            }
        } else {
            header('Content-type: application/json; charset=utf-8', true, $errorType);
            if (is_array($msg)) {
                echo json_encode($msg);
            } elseif ($msg != '') {
                echo $msg;
            }
        }

        die;
    }

    /**
     *  /brief Preenche o template de erro com as variáveis
     *  /param $tpl - HTML carregado do template
     *  /param $errorId - ID do erro
     *  /param $additionalInfo - Informação adicional
     *  /return Retorna o HTML.
     */
    private static function _parseTemplate($tpl, $errorId, $msg, $additionalInfo)
    {
        $getParams = [];
        foreach (URI::getParams() as $var => $value) {
            $getParams[] = $var.'='.(is_array($value) ? json_encode($value) : $value);
        }

        try {
            $uname = php_uname('n');
        } catch (Exception $e) {
            $uname = $_SERVER['HOST_NAME'];
        }

        $tpl = preg_replace('/<!-- DESCRIPTION -->/', $msg, $tpl);
        $tpl = preg_replace('/<!-- ERROR_ID -->/', $errorId, $tpl);
        $tpl = preg_replace('/<!-- EXEC_TIME -->/', Kernel::runTime(), $tpl);
        $tpl = preg_replace('/<!-- SYSTEM -->/', $uname, $tpl);
        $tpl = preg_replace('/<!-- HTTPS -->/', (ini_get('safe_mode') ? 'Yes' : 'No'), $tpl);
        $tpl = preg_replace('/<!-- DATE_TIME -->/', date('Y-m-d G:i:s'), $tpl);
        $tpl = preg_replace('/<!-- REQUEST_URI -->/', isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : 'empty', $tpl);
        $tpl = preg_replace('/<!-- REQUEST_METHOD -->/', isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'empty', $tpl);
        $tpl = preg_replace('/<!-- SERVER_PROTOCOL -->/', isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'empty', $tpl);
        $tpl = preg_replace('/<!-- URL -->/', URI::getURIString().'?'.implode('&', $getParams), $tpl);
        $tpl = preg_replace('/<!-- KERNEL_DEBUG -->/', Kernel::getDebugContent(), $tpl);
        $tpl = preg_replace('/<!-- BACKTRACE -->/', Kernel::makeDebugBacktrace(), $tpl);
        $tpl = preg_replace('/<!-- HTTP_REFERER -->/', isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '', $tpl);
        $tpl = preg_replace('/<!-- CLIENT_IP -->/', Strings::getRealRemoteAddr(), $tpl);
        $tpl = preg_replace('/<!-- REVERSE -->/', (Strings::getRealRemoteAddr() ? gethostbyaddr(Strings::getRealRemoteAddr()) : 'no reverse'), $tpl);
        $tpl = preg_replace('/<!-- HTTP_USER_AGENT -->/', isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '', $tpl);
        $tpl = preg_replace('/<!-- ADDITIONAL_INFO -->/', $additionalInfo, $tpl);
        $tpl = preg_replace('/<!-- _SERVER -->/', Kernel::print_rc($_SERVER), $tpl);
        $tpl = preg_replace('/<!-- _POST -->/', Kernel::print_rc($_POST), $tpl);
        $tpl = preg_replace('/<!-- _GET -->/', Kernel::print_rc($_GET), $tpl);
        $tpl = preg_replace('/<!-- _COOKIE -->/', Kernel::print_rc($_COOKIE), $tpl);
        $tpl = preg_replace('/<!-- _SESSION -->/', Kernel::print_rc(Session::getAll()), $tpl);
        $tpl = preg_replace(['!/\*.*?\*/!s', "/\n\s+/", "/\n(\s*\n)+/", "!\n//.*?\n!s", "/\n\}(.+?)\n/", "/\}\s+/", "/,\n/", "/>\n/", "/\{\s*?\n/", "/\}\n/", "/;\n/"], ['', "\n", "\n", "\n", "}\\1\n", '}', ', ', '>', '{', '} ', ';'], $tpl);

        return $tpl;
    }
}
