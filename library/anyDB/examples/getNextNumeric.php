<?php
require "connect.inc.php";

// get the next result set as a numeric array
if ($db->query("SELECT name, email FROM users where id=2")) {
	$res = $db->getNext(ANYDB_RES_NUM);
    echo DBHelper::dumpNext($res, true);
} else {
	echo $db->error;
}

require "disconnect.inc.php";
////////////////////////////////////////////////////////////////////////
echo '<hr>';
highlight_file(__FILE__);
?>