<?php 
require "connect.inc.php";

// get a single value from the database 
if ($db->query("SELECT count(*) FROM users")) { 
    $count = $db->getValue(); 
    echo $count; 
} else { 
    echo $db->error; 
} 

require "disconnect.inc.php";
////////////////////////////////////////////////////////////////////////
echo '<hr>';
highlight_file(__FILE__);
?>