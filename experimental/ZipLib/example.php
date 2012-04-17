<?php
ob_start();
require('zip.lib.php');

$zipfile = new zipfile('archive.zip');
$zipfile->addDirContent('/files');
echo $zipfile->file();
?>
