<?php
require "connect.inc.php";

// get the next result set

if ($db->query("SELECT name, email FROM users where id=2")) {
	$res = $db->getNext();
	echo implode(' - ', $res) . '<br>';
} else {
	echo $db->error;
}

require "disconnect.inc.php";
////////////////////////////////////////////////////////////////////////
echo '<hr>';
highlight_file(__FILE__);
?>



