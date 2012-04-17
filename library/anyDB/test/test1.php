<?php
///////////////////////////////////////////////////////////// 

require_once 'test.inc.php';

///////////////////////////////////////////////////////////// 
// test script to check if all db functions work
///////////////////////////////////////////////////////////// 

//connect to db
if ($db->connect($host, $database, $user, $password, $persistent)) {
echo $db->error;
    // show status stuff
    echo 'anyDB v' . $db->getVersion();
    echo '<br>';
    echo $db->getDbType() . ' @ ' . $db->getIdentifier();
    echo '<br>';
    echo ($db->persistent ? '' : 'non ') . 'persistent connection';
    echo '<p>';
    echo DBHelper::getCount($db, $table) . ' entries in table "' . $table . '"';
    echo '<br>';

/*
echo $db->query("CREATE TABLE users(id integer NOT NULL AUTO_INCREMENT, name varchar(20) NOT NULL, login varchar (20) NOT NULL, email varchar(20))");
echo $db->query("INSERT INTO users(name,login,email) VALUES('peter','peter','peter@peter.de')");
echo $db->query("INSERT INTO users(name,login,email) VALUES('uschi','uschi','uschi@uschi.de')");
*/

    echo '<hr>';
    
    // show all user tables
    echo 'getTables(): ';
    echo '<br>';
    $tables = $db->getTables();
    echo DBHelper::dumpColumn($tables, false);
    echo $db->error;
    echo '<hr>';
    
    // get a full result set via execute
    $query = "select * from $table limit 2";
    echo 'execute(): ' . $query;
    echo '<br>';
    echo DBHelper::dumpAll($db->execute($query), true);
    echo '<br>';
    echo $db->error;
    
    echo '<hr>';
    
    // get a full result set
    $query = "select * from $table limit 3";
    $db->query($query);
    echo 'getAll(): ' . $query;
    echo '<br>';
    echo DBHelper::dumpAll($db->getAll(), true);
    echo '<br>';
    echo $db->error;
    
    echo '<hr>';
    
    // get a row
    $query = "select * from $table";
    $db->query($query);
    echo 'getNext(ANYDB_RES_NUM): ' . $query;
    echo '<br>';
    echo DBHelper::dumpNext($db->getNext(ANYDB_RES_NUM), true);
    echo '<br>';
    echo $db->error;
    echo '<br>';
    echo DBHelper::dumpNext($db->getNext(ANYDB_RES_NUM), true);
    echo '<br>';
    echo $db->error;
    
    echo '<hr>';
    
    // get a row
    $query = "select * from $table";
    $db->query($query);
    echo 'getNext(ANYDB_RES_ASSOC): ' . $query;
    echo '<br>';
    echo DBHelper::dumpNext($db->getNext(ANYDB_RES_ASSOC), true);
    echo '<br>';
    echo $db->error;
    echo '<br>';
    echo DBHelper::dumpNext($db->getNext(ANYDB_RES_ASSOC), true);
    echo '<br>';
    echo $db->error;
    
    echo '<hr>';
    
    // get a row
    $query = "select * from $table";
    $db->query($query);
    echo 'getNext(ANYDB_RES_BOTH): ' . $query;
    echo '<br>';
    echo DBHelper::dumpNext($db->getNext(ANYDB_RES_BOTH), true);
    echo '<br>';
    echo $db->error;
    echo '<br>';
    echo DBHelper::dumpNext($db->getNext(ANYDB_RES_BOTH), true);
    echo '<br>';
    echo $db->error;
    
    echo '<hr>';
    
    // get a column
    $query = "select $tableKey from $table where $tableKey<10";
    $db->query($query);
    echo 'getColumn(): ' . $query;
    echo '<br>';
    echo DBHelper::dumpColumn($db->getColumn(), true);
    echo '<br>';
    echo $db->error;
    
    echo '<hr>';
    
    // show widget
    echo 'new PageWidget() :';
    echo '<br>';
    $p = new PageWidget($db, $limitFunction, $table, $pageLimit);
    echo '<br>';
    echo $p->getPageDropDown();
    echo DBHelper::dumpAll($p->get());
    $prev = $p->getPrevLink();
    $next = $p->getNextLink();
    echo ($prev ? $prev : 'Previous') . ' - ' .  ( $next ? $next : 'Next');
    echo '<p>';
    echo $p->getIndex();
    
    echo '<hr>';
    
    $db->free();
    $db->disconnect();


} else {
    echo $db->error;
}

///////////////////////////////////////////////////////////// 
?>