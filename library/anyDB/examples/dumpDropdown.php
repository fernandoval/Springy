<?php
require "connect.inc.php";

// get all a column and put in a html select box

if ($db->query("SELECT name FROM users")) {
    $res = $db->getColumn();
    echo DBHelper::selectBox($res, 0, 'mybox');
}

require "disconnect.inc.php";
////////////////////////////////////////////////////////////////////////
echo '<hr>';
highlight_file(__FILE__);
?>