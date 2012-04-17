<?php
$db->free();
$db->disconnect();

echo $db->error;
?>