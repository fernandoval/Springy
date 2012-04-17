<?php
require "connect.inc.php";

// get all the results

if ($db->query("SELECT name, email FROM users")) {
	$results = $db->getAll();
	foreach ($results as $res) {
		echo implode(' - ', $res) . '<br>';
	}
} else {
	echo $db->error;
}

require "disconnect.inc.php";
////////////////////////////////////////////////////////////////////////
echo '<hr>';
highlight_file(__FILE__);
?>