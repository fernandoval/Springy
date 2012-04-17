<?php 
require "connect.inc.php";
require "../addon/Exporter.php";

// export table content as csv data
$csv = Exporter::getTable($db, 'users', ANYDB_DUMP_CSV);

echo nl2br($csv);

require "disconnect.inc.php";
////////////////////////////////////////////////////////////////////////
echo '<hr>';
highlight_file(__FILE__);
?>