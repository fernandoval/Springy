<?php
require "connect.inc.php";


// get all the results and print them in a html table
$results = $db->execute("SELECT name, email FROM users");
echo DBHelper::dumpAll($results, true, array('Name','Email Adress'));


require "disconnect.inc.php";
////////////////////////////////////////////////////////////////////////
echo '<hr>';
highlight_file(__FILE__);
?>