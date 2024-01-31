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
 * @version   3.2.1
 */

namespace Springy;

use Exception;
use PDOException;
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
     * @param string    $errorId
     * @param Throwable $error
     *
     * @return void
     */
    private function cliErrMsg(string $errorId, Throwable $error): void
    {
        $ln = "\n";

        echo 'Error ID: ' . $errorId . $ln;
        echo 'Description: ' . $this->getErrorName($error) . ' - ' . $error->getMessage() . $ln;
        echo 'File: ' . $error->getFile() . $ln;
        echo 'Line: ' . $error->getLine() . $ln;
        echo 'Run time: ' . Kernel::runTime() . ' secs' . $ln;
        echo 'Date/Time: ' . date('c') . $ln;
        echo 'Request: ' . ($_SERVER['REQUEST_URI'] ?? 'undefined') . $ln . $ln;

        foreach ($error->getTrace() as $index => $trace) {
            echo str_pad('#' . $index . ': ', 8, ' ', STR_PAD_LEFT);

            switch ($trace['type'] ?? '') {
                case '->':
                case '::':
                    echo $trace['class'] . $trace['type'] . $trace['function'] . '()' . $ln;
                    break;
                default:
                    echo $trace['function'] . '()' . $ln;
            }

            if (array_key_exists('line', $trace)) {
                echo ' at ' . $trace['file'] . ': ' . $trace['line'] . $ln;
            }
        }

        echo $ln;
    }

    /**
     * Converts error to JSON string.
     *
     * @param Throwable $error
     *
     * @return string
     */
    protected function errorToJson(Throwable $error): string
    {
        return json_encode([
            'error' => [
                'name' => $this->getErrorName($error),
                'code' => $error->getCode(),
                'message' => $error->getMessage(),
                'file' => $error->getFile(),
                'line' => $error->getLine(),
            ],
            'system' => [
                'name' => $this->getUname(),
                'timestamp' => date('c', $_SERVER['REQUEST_TIME'] ?? null),
                'exec_time' => Kernel::runTime(),
            ],
            'request' => [
                'host' => $_SERVER['HTTP_ORIGIN'] ?? $_SERVER['HTTP_HOST'] ?? null,
                'uri' => $_SERVER['REQUEST_URI'] ?? null,
                'method' => $_SERVER['REQUEST_METHOD'] ?? null,
                'protocol' => $_SERVER['SERVER_PROTOCOL'] ?? null,
                'query_string' => $_SERVER['QUERY_STRING'] ?? null,
                'content_type' => $_SERVER['CONTENT_TYPE'] ?? null,
            ],
            'client' => [
                'ip' => Strings::getRealRemoteAddr(),
                'referer' => $_SERVER['HTTP_REFERER'] ?? null,
                'reverse' => Strings::getRealRemoteAddr()
                    ? gethostbyaddr(Strings::getRealRemoteAddr())
                    : null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            ],
            'backtrace' => $error->getTrace(),
        ]);
    }

    /**
     * Translates error code to human readable string.
     *
     * @param Throwable $error
     *
     * @return string
     */
    protected function getErrorName(Throwable $error): string
    {
        $name = [
            E_ERROR => 'Error',
            E_WARNING => 'Warning',
            E_PARSE => 'Parse Error',
            E_NOTICE => 'Notice',
            E_CORE_ERROR => 'Core Error',
            E_CORE_WARNING => 'Core Warning',
            E_COMPILE_ERROR => 'Compile Error',
            E_COMPILE_WARNING => 'Compile Warning',
            E_USER_ERROR => 'User Error',
            E_USER_WARNING => 'User Warning',
            E_USER_NOTICE => 'User Notice',
            E_STRICT => 'Fatal Error',
            1044 => 'Access Denied to Database',
            E_DEPRECATED => 'Deprecated',
            E_USER_DEPRECATED => 'Deprecated by User',
            E_RECOVERABLE_ERROR => 'Recoverable Error',
        ];

        return $name[$error->getCode()] ?? 'Unknown Error (' . $error->getCode() . ')';
    }

    /**
     * Returns HTML template file path.
     *
     * @param string $fileName
     *
     * @return string
     */
    protected function getTplPath(string $fileName): string
    {
        return __DIR__ . DS . 'assets' . DS . $fileName;
    }

    /**
     * Returns information about the OS.
     *
     * @return string
     */
    protected function getUname(): string
    {
        try {
            return php_uname('n');
        } catch (Exception $err) {
            return $_SERVER['HOST_NAME'] ?? $err->getMessage();
        }
    }

    /**
     * Handlers the error.
     *
     * @param int|string $errno
     * @param string     $errstr
     * @param string     $errfile
     * @param int        $errline
     * @param mixed      $errcontext
     * @param int        $errorType
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
     * Tests if application can be recoverable from a deprecated exception.
     *
     * @param Throwable $error
     *
     * @return bool
     */
    protected function isRecoverableDeprecated(Throwable $error): bool
    {
        return ($error->getCode() === E_DEPRECATED || $error->getCode() === E_USER_DEPRECATED)
            && Configuration::get('system', 'ignore_deprecated');
    }

    /**
     * Tests if application can be recoverable from a notice exception.
     *
     * @param Throwable $error
     *
     * @return bool
     */
    protected function isRecoverableNotice(Throwable $error): bool
    {
        if ($error->getCode() !== E_NOTICE) {
            return false;
        }

        $tplPath = Configuration::get('template', 'compiled_template_path');

        return substr($error->getFile(), 0, strlen($tplPath)) === $tplPath;
    }

    /**
     * Tests if application can be recoverable from a waring exception.
     *
     * @param Throwable $error
     *
     * @return bool
     */
    protected function isRecoverableWarning(Throwable $error): bool
    {
        if ($error->getCode() !== E_WARNING) {
            return false;
        }

        $aFile = explode(DIRECTORY_SEPARATOR, $error->getFile());

        if (
            in_array('smarty', $aFile)
            || strpos($error->getMessage(), 'filemtime') !== false
            || strpos($error->getMessage(), 'unlink') !== false
            || (
                in_array('Twig', $aFile)
                && strpos($error->getMessage(), 'include_once') !== false
            )
        ) {
            return true;
        }

        $tplPath = Configuration::get('template', 'compiled_template_path');

        return substr($error->getFile(), 0, strlen($tplPath)) === $tplPath;
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
        if (
            in_array($error->getCode(), Kernel::getIgnoredError())
            || $this->isRecoverableDeprecated($error)
            || $this->isRecoverableNotice($error)
            || $this->isRecoverableWarning($error)
        ) {
            return;
        }

        DB::rollBackAll();
        $printError = $this->getErrorName($error);

        $this->sendReport(
            hash(
                'crc32',
                $error->getCode() . $error->getMessage() . $error->getFile() . $error->getLine()
            ),
            sprintf(
                '%s - %s in %s on line %s',
                $printError,
                $error->getMessage(),
                $error->getFile(),
                $error->getLine()
            ),
            $httpCode,
            $error
        );
    }

    /**
     * Saves error log in database, if configured.
     *
     * @param string    $errorId
     * @param Throwable $error
     *
     * @return bool Returns true if log was inserted and false in othercase.
     */
    protected function saveInDb(string $errorId, Throwable $error): bool
    {
        if (!config_get('system.system_error.save_in_database')) {
            return true;
        }

        $dbsrv = config_get('system.system_error.db_server') ?: 'default';
        $table = config_get('system.system_error.table_name') ?: '_system_errors';
        $description = sprintf(
            '%s - %s in %s [%s]',
            $this->getErrorName($error),
            $error->getMessage(),
            $error->getFile(),
            $error->getLine()
        );
        $command = 'INSERT INTO ' . $table . ' (error_code, description, details, occurrences) VALUES (?, ?, ?, 1)';
        $params = [$errorId, $description, $this->errorToJson($error)];

        $dbc = new DB($dbsrv);

        if (!DB::connected($dbsrv)) {
            return true;
        }

        $dbc->errorReportStatus(false);

        try {
            $dbc->execute('SELECT id FROM ' . $table . ' WHERE error_code = ?', [$errorId]);
            $res = $dbc->fetchNext();

            if ($res !== false) {
                $command = 'UPDATE ' . $table . ' SET occurrences = occurrences + 1 WHERE error_code = ?';
                $params = [$errorId];
            }
        } catch (PDOException $err) {
            if (!$this->wasErrorTableCreated($dbc, $table)) {
                return true;
            }
        }

        $dbc->execute($command, $params);

        return $res === false;
    }

    /**
     * Send notification email message to the system admin.
     *
     * @param string     $errorId
     * @param int|string $errorType
     * @param Throwable  $error
     *
     * @return void
     */
    protected function sendEmail(string $errorId, $errorType, Throwable $error): void
    {
        $html = file_get_contents($this->getTplPath('system-error-email.html'));
        $html = str_replace('{systemName}', app_name(), $html);
        $html = str_replace('{sistemVersion}', app_version(), $html);
        $html = str_replace('{errorId}', $errorId, $html);
        $html = str_replace('{errorCode}', $errorType, $html);
        $html = str_replace('{errorName}', $this->getErrorName($error), $html);
        $html = str_replace('{errorMessage}', $error->getMessage(), $html);
        $html = str_replace('{errorFile}', $error->getFile(), $html);
        $html = str_replace('{errorLine}', $error->getLine(), $html);

        $email = new Mail();
        $email->to(config_get('mail.errors_go_to'), 'System Admin');
        $email->from(
            config_get('mail.system_adm_mail'),
            app_name() . ' - System Error Report'
        );
        $email->subject(
            'Error on ' . app_name() .
            ' v' . app_version() .
            ' [' . Kernel::environment() .
            '] at ' . URI::getHost()
        );
        $email->body($html);
        $email->send();
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

        $reportedErrors = config_get('system.system_error.reported_errors');

        if (is_string($reportedErrors)) {
            $reportedErrors = explode(',', $reportedErrors);
        }

        $isReported = is_array($reportedErrors) ? in_array($httpCode, $reportedErrors) : true;

        if ($isReported && $this->saveInDb($errorId, $error) && config_get('mail.errors_go_to')) {
            $this->sendEmail($errorId, $httpCode, $error);
        }

        Kernel::callErrorHook($httpCode, $title, $errorId, '');
        $this->printHtml($errorId, $httpCode, $error);

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
    public function bugSolved(string $errorId): void
    {
        $dbsrv = config_get('system.system_error.db_server') ?: 'default';
        $table = config_get('system.system_error.table_name') ?: 'system_errors';
        $dbcon = new DB($dbsrv);

        if (!DB::connected($dbsrv)) {
            throw_error(500, 'Fail to connect to database');
        }

        if ($errorId == 'all') {
            $dbcon->execute('DELETE FROM ' . $table, []);
            echo '<strong>ALL</strong> errors deleted from error log.';

            return;
        }

        $idList = explode(',', $errorId);
        $dbcon->execute(
            'DELETE FROM ' . $table
            . ' WHERE error_code ' . (
                count($idList) > 1
                    ? 'in (' . implode(',', array_fill(0, count($idList), '?')) . ')'
                    : '= ?'
            ),
            count($idList) > 1 ? $idList : [$errorId]
        );
        echo 'Error(s) ID <strong>' . $errorId . '</strong> deleted from log.';
    }

    /**
     * Prints the error log content.
     *
     * @return void
     */
    public function bugList()
    {
        $template = $this->getTplPath('errors-list.html');

        if (!is_file($template)) {
            throw_error(404, 'Not Found');
        }

        $dbsrv = config_get('system.system_error.db_server') ?: 'default';
        $table = config_get('system.system_error.table_name') ?: 'system_errors';
        $dbcon = new DB($dbsrv);

        if (!DB::connected($dbsrv)) {
            throw_error(500, 'Fail to connect to database');
        }

        $order_column = URI::getParam('orderBy') ?: 'last_time';
        $order_type = URI::getParam('sort') ?: 'DESC';
        $dbcon->execute(
            'SELECT id, error_code, description, occurrences, last_time, details' .
            '  FROM ' . $table .
            ' ORDER BY ' . $order_column . ' ' . $order_type
        );
        $errList = array_map(
            function ($row) {
                $json = json_decode($row['details']);

                if (!json_last_error()) {
                    $row['details'] = $json;
                }

                return $row;
            },
            $dbcon->fetchAll()
        );

        $output = file_get_contents($template);
        $output = str_replace('{systemName}', app_name(), $output);
        $output = str_replace('{sistemVersion}', app_version(), $output);
        $output = str_replace('{errorsList}', json_encode($errList), $output);

        header('Content-type: text/html; charset=UTF-8', true, 200);
        echo $output;
    }

    /**
     * Prints the error message and quits the application.
     *
     * @param string     $errorId
     * @param int|string $errorType
     * @param Throwable  $error
     *
     * @return void
     */
    private function printHtml(string $errorId, $errorType, Throwable $error)
    {
        $lineFeed = "\n";

        if (ob_get_contents()) {
            ob_clean();
        }

        if (PHP_SAPI === 'cli' || defined('STDIN')) {
            $this->cliErrMsg($errorId, $error);

            return;
        }

        header('Content-type: text/html; charset=UTF-8', true, $errorType ?? 0);

        if (Kernel::isCGIMode()) {
            echo 'Status: ' . $errorType . $lineFeed . $lineFeed;
        }

        if (!is_null(config_get('template.template_engine'))) {
            $tplName = config_get('template.errors.' . $errorType) ?: '_error' . $errorType;
            $tpl = new Template();

            if ($tpl->templateExists($tplName)) {
                $tpl->setTemplate($tplName);
                $tpl->assign('errorDebug', config_get('system.debug'));
                $tpl->assign('errorId', $errorId);
                $tpl->assign('errorName', $this->getErrorName($error));
                $tpl->assign('errorMessage', $error->getMessage());
                $tpl->assign('errorFile', $error->getFile());
                $tpl->assign('errorLine', $error->getLine());
                $tpl->display();

                return;
            }
        }

        $output = file_get_contents($this->getTplPath('system-error.html'));
        $output = str_replace('{systemName}', app_name(), $output);
        $output = str_replace('{sistemVersion}', app_version(), $output);
        $output = str_replace('{errorId}', $errorId, $output);
        $output = str_replace('{errorCode}', $errorType, $output);
        echo $output;
    }

    /**
     * Attempts to create the application error log table.
     *
     * @param DB     $dbc
     * @param string $table
     *
     * @return bool
     */
    private function wasErrorTableCreated(DB $dbc, string $table): bool
    {
        $create = file_get_contents(__DIR__ . DS . 'system_errors_create_table.sql');

        if (!config_get('system.system_error.create_table') || !$create) {
            return false;
        }

        try {
            $dbc->execute(str_replace('%table_name%', $table, $create));
        } catch (\Throwable $err) {
            return false;
        }

        return true;
    }
}
