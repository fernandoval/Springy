<?php
////////////////////////////////////////////////////////////////////////

$dir = dirname(__FILE__) . '/';
require_once $dir . '../anyDB.php';
require_once $dir . '../addon/DBHelper.php';
require_once $dir . '../addon/QueryHelper.php';
require_once $dir . '../addon/PageWidget.php';

// wrapper paths
$pearPath = $dir . '../../../inc/pear/';
$phplibPath = $dir . '../../../inc/phplib-7.2d/';
$metabasePath = $dir . '../../../inc/metabase/';
$adodbPath = $dir . '../../../inc/adodb/';
$ddbxPath = $dir . '../../3rd_party/dbx/';

// connection info
$database = 'test';
$host = 'localhost';
$user = '';
$password = '';

// db info
//$dbType = 'sqlite';
//$dbType = 'dbx';
$dbType = 'mysql';
//$dbType = 'postgres';

$resType = ANYDB_RES_ASSOC;
//$resType = ANYDB_RES_NUM;
//$resType = ANYDB_RES_BOTH;

$persistent = false;
//$persistent = true;

// table info
$table = 'users';
$tableKey = 'id';

// PageWidget info
$pageLimit = 1;
//$limitFunction = 'dbxLimitQuery';
//$limitFunction = 'sqliteLimitQuery';
$limitFunction = 'mysqlLimitQuery';
//$limitFunction = 'postgresLimitQuery';

// get the db layer
//$db = anyDB::getLayer('SQLITE', $ddbxPath, $dbType, $resType);
//$db = anyDB::getLayer('DBX', $ddbxPath, $dbType, $resType);
$db = anyDB::getLayer('MYSQL','', $dbType, $resType);
//$db = anyDB::getLayer('PGSQL','', $dbType, $resType);
//$db = anyDB::getLayer('PEAR', $pearPath, $dbType, $resType);
//$db = anyDB::getLayer('PHPLIB', $phplibPath, $dbType, $resType);
//$db = anyDB::getLayer('METABASE', $metabasePath, $dbType, $resType);
//$db = anyDB::getLayer('ADODB', $adodbPath, $dbType, $resType);

////////////////////////////////////////////////////////////////////////
?>
