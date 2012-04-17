<?php
require "connect.inc.php";

// get all one column and print them in a table


if ($db->query("SELECT name FROM users")) {
	$res = $db->getColumn();
	echo DBHelper::dumpColumn($res, true, 'name');
	echo '<br>';
	echo DBHelper::dumpColumn($res, false, 'name');
} else {
	echo $db->error;
}

require "disconnect.inc.php";
////////////////////////////////////////////////////////////////////////
echo '<hr>';
highlight_file(__FILE__);
?>