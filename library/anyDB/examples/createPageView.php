<?php 
require "connect.inc.php";

// create a new page widget 
require_once '../addon/PageWidget.php'; 
// widget settings 
$entries = 1; 
$table = 'users'; 
// which rows to display 
$rows = array('name', 'email'); 
$widget = new PageWidget($db, 'mysqlLimitQuery', $table, $entries); 

echo "<h2>Page $widget->page</h2>"; 
echo $widget->getPageDropdown(); 
echo "Entries $widget->start -  $widget->end of $widget->total<p>"; 
echo '[' . $widget->getIndex('] [') .']<p>'; 
echo $widget->getOrderDropdown($rows); 
echo $widget->getNextLink(); 
echo '<br>'; 
echo $widget->getPrevLink(); 
// get the data 
echo DBHelper::dumpAll($widget->get($rows), true); 

require "disconnect.inc.php";
////////////////////////////////////////////////////////////////////////
echo '<hr>';
highlight_file(__FILE__);
?> 