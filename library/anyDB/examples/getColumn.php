<?php
require "connect.inc.php";

// get one column
if ($db->query("SELECT name FROM users")) {
	$results = $db->getColumn();
	foreach ($results as $res) {
		echo $res . '<br>';
	}
} else {
	echo $db->error;
}

require "disconnect.inc.php";
////////////////////////////////////////////////////////////////////////
echo '<hr>';
highlight_file(__FILE__);
?>