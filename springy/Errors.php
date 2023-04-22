<?php

/**
 * Error handler class.
 *
 * This class is used by framework to error handler and throw it by user class.
 *
 * @copyright 2007 Fernando Val
 * @author    Fernando Val <fernando.val@gmail.com>
 * @author    Lucas Cardozo <lucas.cardozo@gmail.com>
 * @license   https://github.com/fernandoval/Springy/blob/master/LICENSE MIT
 *
 * @version   3.0.53
 */

namespace Springy;

use Exception;
use Springy\Core\Debug;
use Springy\Exceptions\SpringyException;
use Springy\Utils\Strings;
use Throwable;

/**
 * Error handler class.
 */
class Errors
{
    /**
     * Constructor method.
     *
     * Warning! If $httpStatus is greater or equal 400 the application handler
     * error will be started.
     *
     * @param int    $httpStatus is the HTTP status code that will be set to
     *                           header.
     * @param string $message    is a text to print in error message.
     */
    public function __construct($httpStatus = 200, $message = '')
    {
        if ($httpStatus >= 400) {
            $this->process(
                new SpringyException($message, E_USER_ERROR),
                $httpStatus
            );
        }
    }

    /**
     * Error message for cli mode.
     *
     * @param string $errorId
     * @param string $desc
     *
     * @return string
     */
    private function cliErrMsg(string $errorId, string $desc): string
    {
        $ln = "\n";

        $error = 'Error ID: ' . $errorId . $ln .
            'Description: ' . $desc . $ln .
            'Run time: ' . Kernel::runTime() . ' seconds' . $ln .
            'Date: ' . date('Y-m-d') . $ln .
            'Time: ' . date('G:i:s') . $ln .
            'Request: ' . (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : 'undefined') . $ln . $ln;

        $btrace = array_reverse(debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT));

        foreach ($btrace as $index => $trace) {
            $error .= str_pad('#' . $index . ': ', 8, ' ', STR_PAD_LEFT);

            switch ($trace['type'] ?? '') {
                case '->':
                case '::':
                    $error .= $trace['class'] . $trace['type'] . $trace['function'] . '()';
                    break;
                default:
                    $error .= $trace['function'] . '()';
            }

            if (array_key_exists('line', $trace)) {
                $error .= ' at ' . $trace['file'] . ': ' . $trace['line'] . $ln;
            }
        }

        return $error;
    }

    /**
     * Generate HTML with error informations.
     *
     * @param string $title
     * @param string $errorId
     *
     * @return string
     */
    protected function generateOutputMessage(string $title, string $errorId, Throwable $error): string
    {
        $getParams = [];
        foreach (URI::getParams() as $var => $value) {
            $getParams[] = $var . '=' . (is_array($value) ? json_encode($value) : $value);
        }

        $errorTemplate = __DIR__ . DS . 'error_template.html';
        $temmplate = '<table width="100%" border="0" cellspacing="0" cellpadding="0">'
            . '<tr><td>Error Description</td></tr>'
            . '<tr><td><!-- DESCRIPTION --></td></tr>'
            . '</table>'
            . '<table width="100%" border="0" cellspacing="0" cellpadding="0">'
            . '<tr><td colspan="2">Debug Information</td></tr>'
            . '<tr><td>Execution time:</td><td><!-- EXEC_TIME --> seconds</td></tr>'
            . '<tr><td>System:</td><td><!-- SYSTEM --></td></tr>'
            . '<tr><td>HTTPS:</td><td><!-- HTTPS --></td></tr>'
            . '<tr><td>Date:</td><td><!-- DATE_TIME --></td></tr>'
            . '<tr><td>Request:</td><td><!-- REQUEST_URI --></td></tr>'
            . '<tr><td>Request Method:</td><td><!-- REQUEST_METHOD --></td></tr>'
            . '<tr><td>Server Protocol:</td><td><!-- SERVER_PROTOCOL --></td></tr>'
            . '<tr><td>URL:</td><td><!-- URL --></td></tr>'
            . '<tr><td colspan="2">Debug:</td></tr>'
            . '<tr><td colspan="2"><!-- KERNEL_DEBUG --></td></tr>'
            . '<tr><td colspan="2">Info:</td></tr>'
            . '<tr><td colspan="2"><!-- BACKTRACE --></td></tr>'
            . '<tr><td colspan="2">Client Information</td></tr>'
            . '<tr><td>HTTP Referer:</td><td><!-- HTTP_REFERER --></td></tr>'
            . '<tr><td>Client IP:</td><td><!-- CLIENT_IP --></td></tr>'
            . '<tr><td>Reverse:</td><td><!-- REVERSE --></td></tr>'
            . '<tr><td>User Agent:</td><td><!-- HTTP_USER_AGENT --></td></tr>'
            . '<tr><td colspan="2">PHP vars</td></tr>'
            . '<tr><td valign="top">_POST</td><td><!-- _POST --></td></tr>'
            . '<tr><td valign="top">_GET</td><td><!-- _GET --></td></tr>'
            . '<tr><td valign="top">_COOKIE</td><td><!-- _COOKIE --></td></tr>'
            . '</table>';

        if (file_exists($errorTemplate)) {
            $temmplate = file_get_contents($errorTemplate) ?: $temmplate;
        }

        return $this->parseTemplate($temmplate, $errorId, $title, $error);
    }

    /**
     * Handlers the error.
     *
     * @param int|string  $errno
     * @param string      $errstr
     * @param string      $errfile
     * @param int         $errline
     * @param mixed       $errcontext
     * @param int         $errorType
     *
     * @return void
     *
     * @deprecated 4.5
     */
    public function handler($errno, $errstr, $errfile, $errline, $errcontext = null, $errorType = 500)
    {
        $this->process(
            new SpringyException($errstr, $errno, null, $errfile, $errline),
            $errorType
        );
    }

    /**
     * Errors and exceptions final handler.
     *
     * @param int|string $errno
     * @param string     $errstr
     * @param string     $errfile
     * @param int        $errline
     * @param int        $httpCode
     *
     * @return void
     */
    public function process(Throwable $error, $httpCode = 500)
    {
        if (in_array($error->getCode(), Kernel::getIgnoredError())) {
            return;
        }

        DB::rollBackAll();

        switch ($error->getCode()) {
            case E_ERROR:
                $printError = 'Error';
            break;
            case E_WARNING:
                $printError = 'Warning';
                $aFile = explode(DIRECTORY_SEPARATOR, $error->getFile());
                if (
                    in_array('smarty', $aFile)
                    || strpos($error->getMessage(), 'filemtime') !== false
                    || strpos($error->getMessage(), 'unlink') !== false
                ) {
                    return;
                } elseif (
                    in_array('Twig', $aFile)
                    && strpos($error->getMessage(), 'include_once') !== false
                ) {
                    return;
                }
                $tplPash = Configuration::get('template', 'compiled_template_path');
                if (substr($error->getFile(), 0, strlen($tplPash)) == $tplPash) {
                    return;
                }
            break;
            case E_PARSE:
                $printError = 'Parse Error';
            break;
            case E_NOTICE:
                $printError = 'Notice';
                $tplPash = Configuration::get('template', 'compiled_template_path');
                if (substr($error->getFile(), 0, strlen($tplPash)) == $tplPash) {
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
                    return;
                }
            break;
            case E_RECOVERABLE_ERROR:
                $printError = 'Fatal Error';
            break;
            default:
                $printError = 'Unknown Error (' . $error->getCode() . ')';
            break;
        }

        $this->sendReport(
            hash('crc32', $error->getCode() . $error->getFile() . $error->getLine()),
            (PHP_SAPI === 'cli' || defined('STDIN'))
                ? $printError . ' - ' . $error->getMessage()
                    . ' in ' . $error->getFile()
                    . ' on line ' . $error->getLine()
                : '<span style="color:#FF0000">'
                    . $printError . '</span>: <em>'
                    . $error->getMessage() . '</em> in <strong>'
                    . $error->getFile() . '</strong> on line <strong>'
                    . $error->getLine() . '</strong>',
            $httpCode,
            $error
        );
    }

    /**
     * Saves error log in database, if configured.
     *
     * @param string $errorId
     * @param string $msg
     * @param string $details
     *
     * @return bool Returns true if log was inserted and false in othercase.
     */
    protected function saveInDb(string $errorId, string $title, string $details): bool
    {
        if (!Configuration::get('system', 'system_error.save_in_database')) {
            return false;
        }

        $conn = Configuration::get('system', 'system_error.db_server') ?: 'default';
        $table = Configuration::get('system', 'system_error.table_name') ?: 'system_errors';
        $dbc = new DB($conn);

        if (!DB::connected()) {
            return true;
        }

        $dbc->errorReportStatus(false);
        $res = $dbc->execute('SELECT id FROM ' . $table . ' WHERE error_code = ?', [$errorId])
            ? $dbc->fetchNext()
            : false;
        $command = ($res === false)
            ? 'INSERT INTO ' . $table . ' (error_code, description, details, occurrences) VALUES (?, ?, ?, 1)'
            : 'UPDATE ' . $table . ' SET occurrences = occurrences + 1 WHERE error_code = ?';
        $params = ($res === false) ? [$errorId, $title, $details] : [$errorId];
        $create = file_get_contents(__DIR__ . DS . 'system_errors_create_table.sql');

        if (
            $res === false
            && $dbc->statmentErrorCode()
            && Configuration::get('system', 'system_error.create_table')
            && $create
        ) {
            $dbc->execute(str_replace('%table_name%', $table, $create));
        }

        $dbc->execute($command, $params);

        return $res === false;
    }

    /**
     * Sends the error to the webmaster.
     *
     * @param string    $errorId
     * @param string    $title
     * @param int       $httpCode
     * @param Throwable $error
     *
     * @return void
     */
    public function sendReport(string $errorId, string $title, int $httpCode, Throwable $error)
    {
        restore_error_handler();

        $out = $this->generateOutputMessage($title, $errorId, $error);
        $reportedErrors = Configuration::get('system', 'system_error.reported_errors');

        if (is_string($reportedErrors)) {
            $reportedErrors = explode(',', $reportedErrors);
        }

        $isReported = is_array($reportedErrors) ? in_array($httpCode, $reportedErrors) : true;

        if ($isReported && $this->saveInDb($errorId, $title, $out) && Configuration::get('mail', 'errors_go_to')) {
            $errorTemplate = __DIR__ . DS . 'error_mail_template.html';
            $errorMail = preg_replace(
                '/display:none;/',
                '',
                preg_replace(
                    '/\<a href="javascript\:\;" onClick="var obj=\$\(\#(.*?)\)\.toggle\(\)" style="color:#06c; margin:3px 0"\>arguments passed to function\<\/a\>/',
                    '<span style="font-weight:bold; color:#06c; margin:3px 0">Functions arguments:</span>',
                    $out
                )
            );

            if (file_exists($errorTemplate)) {
                $errorMail = $this->parseTemplate(
                    file_get_contents($errorTemplate) ?: $errorMail,
                    $errorId,
                    $title,
                    $error
                );
            }

            $email = new Mail();
            $email->to(Configuration::get('mail', 'errors_go_to'), 'System Admin');
            $email->from(
                Configuration::get('mail', 'system_adm_mail'),
                Kernel::systemName() . ' - System Error Report'
            );
            $email->subject(
                'Error on ' . Kernel::systemName() .
                ' v' . Kernel::systemVersion() .
                ' [' . Kernel::environment() .
                '] at ' . URI::getHost()
            );
            $email->body($errorMail);
            $email->send();
        }

        Kernel::callErrorHook($httpCode, $title, $errorId, '');

        $this->printHtml($errorId, $httpCode, $out);

        // Terminate application with error status code 1.
        exit(1);
    }

    /**
     * Deletes an error from error log table.
     *
     * @param string $errorId
     *
     * @return void
     */
    public function bugSolved($errorId)
    {
        if (!$conn = Configuration::get('system', 'system_error.db_server')) {
            $conn = 'default';
        }
        if (!$table = Configuration::get('system', 'system_error.table_name')) {
            $table = 'system_errors';
        }

        $db = new DB($conn);

        if (DB::connected()) {
            if ($errorId == 'all') {
                $db->execute('DELETE FROM ' . $table, []);
                echo '<strong>ALL</strong> errors deleted from error log.';

                return;
            }

            $db->execute('DELETE FROM ' . $table . ' WHERE error_code = ?', [$errorId]);
            echo 'ID <strong>' . URI::getSegment(1, false) . '</strong> deleted from error log.';
        }
    }

    /**
     * Prints the error log content.
     *
     * @return void
     */
    public function bugList()
    {
        if (!$conn = Configuration::get('system', 'system_error.db_server')) {
            $conn = 'default';
        }
        if (!$table = Configuration::get('system', 'system_error.table_name')) {
            $table = 'system_errors';
        }

        $dbc = new DB($conn);
        $order_column = URI::getParam('orderBy') ?: 'last_time';
        $order_type = URI::getParam('sort') ?: 'DESC';
        $dbc->execute(
            'SELECT id, error_code, description, occurrences, last_time, details' .
            '  FROM ' . $table .
            ' ORDER BY ' . $order_column . ' ' . $order_type
        );

        echo
            '<!DOCTYPE html>',
            '<html>',
                '<head>',
                    '<meta charset="UTF-8">',
                    '<title>',
                        Kernel::systemName(),
                        ' version ',
                        Kernel::systemVersion(),
                        ' - Occurrence Errors</title>',

                    '<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.2/css/bootstrap.min.css">',

                    '<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>',
                    '<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.2/js/bootstrap.min.js"></script>',
                '</head>',
            '<body>',
                '<div class="page-header"><h1 class="text-center">',
                    Kernel::systemName(),
                    ' <small>System Error Occurrences</small></h1></div>',

                '<div class="container">',
                    (
                        $dbc->affectedRows()
                            ? (
                                '<div class="panel-group text-right"><a class="btn btn-xs btn-danger" href="'
                                . URI::buildURL(['_system_bug_solved_', 'all'])
                                . '">DELETE ALL</a></div>'
                            ) : ''
                    ),
                    '<div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true">';

        while ($res = $dbc->fetchNext()) {
            echo
                '<div class="panel panel-default">',
                    '<div class="panel-heading" role="tab" id="heading', $res['id'], '">',
                        '<a data-toggle="collapse" data-parent="#accordion" href="#collapse',
                            $res['id'],
                            '" aria-expanded="true" aria-controls="collapse',
                            $res['id'],
                        '">',
                            '<span class="label label-default">',
                                $res['error_code'],
                            '</span> ',
                            $res['description'],
                            ' <span class="badge">',
                                $res['occurrences'],
                            '</span>',
                        '</a>',
                        '<a class="btn btn-xs btn-primary pull-right" href="',
                            URI::buildURL(['_system_bug_solved_', $res['error_code']]),
                        '">',
                            'DELETE',
                        '</a>',
                    '</div>',
                    '<div id="collapse',
                        $res['id'],
                        '" class="panel-collapse collapse" role="tabpanel" aria-labelledby="heading',
                        $res['id'],
                    '">',
                    '<div class="panel-body">';

            $res['details'] = preg_replace(
                '/<table([a-zA-Z0-9"=\%:,; \-]*)>/',
                '<table class="table table-bordered">',
                $res['details']
            );
            $res['details'] = preg_replace(
                '/<tr style="color:#000; display:none;" class="bugList">/',
                '<tr class="bugList">',
                $res['details']
            );
            $res['details'] = preg_replace(
                '/(.*)<tr class="hideMoreInfo">/',
                '<tr class="hideMoreInfo">',
                $res['details']
            );
            $res['details'] = preg_replace(
                '/(.*)<!-- CONTENT -->/s',
                '',
                $res['details']
            );
            $res['details'] = str_replace(
                '[last_time]',
                $res['last_time'],
                $res['details']
            );

            echo $res['details'];

            echo '</div></div></div>';
        }

        echo '</div></div></body></html>';
    }

    /**
     * Prints the error message and quits the application.
     *
     * @param string     $errorId
     * @param int|string $errorType
     * @param string     $desc
     *
     * @return void
     */
    private function printHtml(string $errorId, $errorType, string $desc)
    {
        $lineFeed = "\n";

        // Verifica se a saída do erro não é em ajax ou json
        if (ob_get_contents()) {
            ob_clean();
        }

        header('Content-type: text/html; charset=UTF-8', true, $errorType ?? 0);

        if (Kernel::isCGIMode()) {
            echo 'Status: ' . $errorType . $lineFeed . $lineFeed;
        }

        if (PHP_SAPI === 'cli' || defined('STDIN')) {
            echo $this->cliErrMsg($errorId, $desc) . $lineFeed;

            return;
        }

        if (!is_null(Configuration::get('template', 'template_engine'))) {
            $tplName = Configuration::get('template', 'errors.' . $errorType) ?: '_error' . $errorType;
            $tpl = new Template();

            if ($tpl->templateExists($tplName)) {
                $tpl->setTemplate($tplName);
                $tpl->assign('errorDebug', (Configuration::get('system', 'debug') ? $desc : ''));
                $tpl->display();

                return;
            }
        }

        echo '<!DOCTYPE html>';
        echo '<html lang="en">';
        echo '  <head>';
        echo '    <meta charset="utf-8">';
        echo '    <meta http-equiv="X-UA-Compatible" content="IE=edge">';
        echo '    <meta name="viewport" content="width=device-width, initial-scale=1">';
        echo '    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
        echo '    <title>' . Kernel::systemName() . ' (' . Kernel::systemVersion() . ')</title>';
        echo '    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.2/css/bootstrap.min.css">';
        echo '    <!--[if lt IE 9]>';
        echo '      <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>';
        echo '      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>';
        echo '    <![endif]-->';
        echo '  </head>';
        echo '  <body>';
        echo '    <h1 class="text-center">Error ' . $errorType . '</h1>';
        if (Configuration::get('system', 'debug')) {
            echo '    <div class="container">';
            echo '      ' . $desc;
            echo '    </div>';
        }
        echo '    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js"></script>';
        echo '    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.2/js/bootstrap.min.js"></script>';
        echo '  </body>';
        echo '</html>';
    }

    /**
     * Puts error details into the template message.
     *
     * @param string    $tpl
     * @param string    $errorId
     * @param string    $desc
     * @param Throwable $error
     *
     * @return string
     */
    protected function parseTemplate(string $tpl, string $errorId, string $desc, Throwable $error): string
    {
        $getParams = [];
        foreach (URI::getParams() as $var => $value) {
            $getParams[] = $var . '=' . (is_array($value) ? json_encode($value) : $value);
        }

        try {
            $uname = php_uname('n');
        } catch (Exception $e) {
            $uname = $_SERVER['HOST_NAME'];
        }

        $tpl = preg_replace('/<!-- DESCRIPTION -->/', $desc, $tpl);
        $tpl = preg_replace('/<!-- ERROR_ID -->/', $errorId, $tpl);
        $tpl = preg_replace('/<!-- EXEC_TIME -->/', Kernel::runTime(), $tpl);
        $tpl = preg_replace('/<!-- SYSTEM -->/', $uname, $tpl);
        $tpl = preg_replace('/<!-- HTTPS -->/', (ini_get('safe_mode') ? 'Yes' : 'No'), $tpl);
        $tpl = preg_replace('/<!-- DATE_TIME -->/', date('Y-m-d G:i:s'), $tpl);
        $tpl = preg_replace('/<!-- REQUEST_URI -->/', $_SERVER['REQUEST_URI'] ?? 'empty', $tpl);
        $tpl = preg_replace('/<!-- REQUEST_METHOD -->/', $_SERVER['REQUEST_METHOD'] ?? 'empty', $tpl);
        $tpl = preg_replace('/<!-- SERVER_PROTOCOL -->/', $_SERVER['SERVER_PROTOCOL'] ?? 'empty', $tpl);
        $tpl = preg_replace('/<!-- HOST -->/', URI::buildURL(), $tpl);
        $tpl = preg_replace('/<!-- URL -->/', URI::getURIString() . '?' . implode('&', $getParams), $tpl);
        $tpl = preg_replace('/<!-- KERNEL_DEBUG -->/', Debug::get(), $tpl);
        $tpl = preg_replace('/<!-- BACKTRACE -->/', Debug::backtrace($error->getTrace()), $tpl);
        $tpl = preg_replace('/<!-- HTTP_REFERER -->/', $_SERVER['HTTP_REFERER'] ?? '', $tpl);
        $tpl = preg_replace('/<!-- CLIENT_IP -->/', Strings::getRealRemoteAddr(), $tpl);
        $tpl = preg_replace(
            '/<!-- REVERSE -->/',
            (
                Strings::getRealRemoteAddr()
                    ? gethostbyaddr(Strings::getRealRemoteAddr())
                    : 'no reverse'
            ),
            $tpl
        );
        $tpl = preg_replace('/<!-- HTTP_USER_AGENT -->/', $_SERVER['HTTP_USER_AGENT'] ?? '', $tpl);
        $tpl = preg_replace('/<!-- _SERVER -->/', Debug::print_rc($_SERVER), $tpl);
        $tpl = preg_replace('/<!-- _POST -->/', Debug::print_rc($_POST), $tpl);
        $tpl = preg_replace('/<!-- _GET -->/', Debug::print_rc($_GET), $tpl);
        $tpl = preg_replace('/<!-- _COOKIE -->/', Debug::print_rc($_COOKIE), $tpl);
        $tpl = preg_replace('/<!-- _SESSION -->/', Debug::print_rc(Session::getAll()), $tpl);
        $tpl = preg_replace(
            [
                '!/\*.*?\*/!s',
                "/\n\s+/",
                "/\n(\s*\n)+/",
                "!\n//.*?\n!s",
                "/\n\}(.+?)\n/",
                "/\}\s+/",
                "/,\n/",
                "/>\n/",
                "/\{\s*?\n/",
                "/\}\n/",
                "/;\n/",
            ],
            [
                '',
                "\n",
                "\n",
                "\n",
                "}\\1\n",
                '}',
                ', ',
                '>',
                '{',
                '} ',
                ';',
            ],
            $tpl
        );

        return $tpl;
    }
}
