<?php
require_once '../anyDB.php';
require_once '../addon/DBHelper.php';
require_once '../addon/QueryHelper.php';

$database = 'test';
$host = 'localhost';
$user = '';
$password = '';
$dbType = 'mysql';

// create a new db layer
$db = anyDB::getLayer('MYSQL','', $dbType);
    //$db = anyDB::getLayer('PEAR', 'c:/php4/pear/', $dbType);
    //$db = anyDB::getLayer('PHPLIB', '../../../inc/phplib-7.2d/', $dbType);
    //$db = anyDB::getLayer('METABASE', '../../../inc/metabase/', $dbType);
    //$db = anyDB::getLayer('ADODB', '../../../inc/adodb/', $dbType);
    //$db = anyDB::getLayer('DBX','../../../inc/dbx/', $dbType);

//connect to db
$db->connect($host, $database, $user, $password);
?>