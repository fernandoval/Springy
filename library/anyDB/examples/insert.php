<?php
require "connect.inc.php";


// insert data in the database
if ($db->query("INSERT INTO users (name, email) VALUES ('lennart','lennart@lennart.de')")) {
	echo 'ok';
} else {
	echo $db->error;
}

require "disconnect.inc.php";
////////////////////////////////////////////////////////////////////////
echo '<hr>';
highlight_file(__FILE__);
?>