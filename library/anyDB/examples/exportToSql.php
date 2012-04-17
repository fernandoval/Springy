<?php 
require "connect.inc.php";
require "../addon/Exporter.php";

// export table content as sql statements
$sqlData = Exporter::getDB($db, ANYDB_DUMP_SQL);

foreach($sqlData as $key => $data) {
    echo "$key<br>";
    echo nl2br($data);
}

require "disconnect.inc.php";
////////////////////////////////////////////////////////////////////////
echo '<hr>';
highlight_file(__FILE__);
?>