<?php
require "connect.inc.php";

// get all the results with execute
$results = $db->execute("SELECT name, email FROM users");
foreach (@$results as $res) {
	echo implode(' - ', $res) . '<br>';
}

require "disconnect.inc.php";
////////////////////////////////////////////////////////////////////////
echo '<hr>';
highlight_file(__FILE__);
?>