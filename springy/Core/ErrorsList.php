<?php

namespace Springy\Core;

use Springy\DB;
use Springy\Exceptions\HttpErrorNotFound;
use Springy\URI;

class ErrorsList
{
    private DB $dbConnection;
    private string $errorTable;

    public function __construct()
    {
        $dbServer = config_get('system.system_error.db_server') ?: 'default';
        $this->errorTable = config_get('system.system_error.table_name') ?: '_system_errors';
        $this->dbConnection = new DB($dbServer);

        if (!$this->dbConnection->isConnected()) {
            throw_error(500, 'Fail to connect to database');
        }
    }

    public function __invoke()
    {
        $errorCode = URI::getSegment(1, false);

        match (true) {
            $errorCode === false => $this->printErrorsList(),
            preg_match('/^\\w{8}$/', $errorCode) === 1 => $this->printErrorDetails($errorCode),
            $errorCode === 'delete' => $this->delete(),
            default => throw new HttpErrorNotFound(),
        };
    }

    private function delete(): void
    {
        $errorCode = URI::getSegment(2, false);

        if ($errorCode == 'all') {
            $this->dbConnection->execute('DELETE FROM ' . $this->errorTable, []);
            echo '<strong>ALL</strong> errors deleted from error log.';

            return;
        }

        $idList = explode(',', $errorCode);
        $this->dbConnection->execute(
            'DELETE FROM ' . $this->errorTable
            . ' WHERE error_code ' . (
                count($idList) > 1
                    ? 'in (' . implode(',', array_fill(0, count($idList), '?')) . ')'
                    : '= ?'
            ),
            count($idList) > 1 ? $idList : [$errorCode]
        );
        echo 'Error(s) ID <strong>' . $errorCode . '</strong> deleted from log.';
    }

    private function getErrorDetail(array $error): array
    {
        $json = json_decode($error['details']);

        if (!json_last_error()) {
            $error['details'] = $json;
        }

        return $error;
    }

    private function getTemplate(string $template): string
    {
        $output = file_get_contents($template);
        $output = str_replace('{systemName}', app_name(), $output);
        $output = str_replace('{sistemVersion}', app_version(), $output);

        return $output;
    }

    private function header(): void
    {
        header('Content-type: text/html; charset=UTF-8', true, 200);
        header('X-Content-Type-Options: nosniff');
        header('X-XSS-Protection: 1; mode=block');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: no-referrer');
        header('Permissions-Policy: interest-cohort=()');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');
    }

    private function printErrorDetails(string $errorCode): void
    {
        $this->dbConnection->execute(
            'SELECT * FROM ' . $this->errorTable . ' WHERE error_code = ?',
            [$errorCode]
        );

        $error = $this->dbConnection->fetchNext();

        if (!$error) {
            throw new HttpErrorNotFound();
        }

        $output = $this->getTemplate(__DIR__ . DS . 'assets' . DS . 'error-details.html');
        $this->header();
        echo str_replace('{errorData}', json_encode($this->getErrorDetail($error)), $output);
    }

    /**
     * Prints the error log content.
     */
    private function printErrorsList(): void
    {
        $order_column = URI::getParam('orderBy') ?: 'last_time';
        $order_type = URI::getParam('sort') ?: 'DESC';
        $this->dbConnection->execute(
            'SELECT id, error_code, description, occurrences, last_time, details' .
            ' FROM ' . $this->errorTable .
            ' ORDER BY ' . $order_column . ' ' . $order_type
        );
        $errList = array_map(
            fn ($row) => $this->getErrorDetail($row),
            $this->dbConnection->fetchAll()
        );

        $output = $this->getTemplate(__DIR__ . DS . 'assets' . DS . 'errors-list.html');
        $this->header();
        echo str_replace('{errorsList}', json_encode($errList), $output);
    }
}
