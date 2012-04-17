<?php
require "connect.inc.php";

// get the next result and put it in a html table

if ($db->query("SELECT name, email FROM users where id=2")) {
	$res = $db->getNext();
	echo DBHelper::dumpNext($res, true);
} else {
	echo $db->error;
}

require "disconnect.inc.php";
////////////////////////////////////////////////////////////////////////
echo '<hr>';
highlight_file(__FILE__);
?>