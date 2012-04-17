<?php
////////////////////////////////////////////////////////////////////////

define ('ANYDB_DUMP_CSV', 1);
define ('ANYDB_DUMP_SQL', 2);

////////////////////////////////////////////////////////////////////////

require_once dirname(__FILE__) . '/../base/UtilityClass.php';

////////////////////////////////////////////////////////////////////////
/**
* Utility class for anyDB
*
* With this class you export db or table content 
*
* @link        http://lensphp.sourceforge.net for the latest version
* @author	   Lennart Groetzbach <lennartg[at]web.de>
* @copyright	Lennart Groetzbach <lennartg[at]web.de> - distributed under the LGPL
*
* @package      anydb
* @access       public
* @version      1.2 - 11/30/04
*
*/
////////////////////////////////////////////////////////////////////////

class Exporter extends UtilityClass {

////////////////////////////////////////////////////////////////////////
/*
    This library is free software; you can redistribute it and/or
    modify it under the terms of the GNU Lesser General Public
    License as published by the Free Software Foundation; either
    version 2.1 of the License, or (at your option) any later version.
    
    This library is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
    Lesser General Public License for more details.
    
    You should have received a copy of the GNU Lesser General Public
    License along with this library; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/
////////////////////////////////////////////////////////////////////////
/**
* Returns the db data content
*
* @access   public
*
* @param    AbstractDB  $db                 db identifier
* @param    Integer     $type               ANYDB_DUMP_ constants
* @param    String      $seperator          for csv files
*
* @returns  Array       the table data
*/
function getDB(& $db, $type = ANYDB_DUMP_SQL, $seperator = "\t") {
    $res = array();
    $tables = $db->getTables();
    if (@sizeof($tables) == 0) {
        die('dumpDB(): No tables found...');
    }
    foreach ($tables as $table) {
        $res[$table] = Exporter::getTable($db, $table, $type, $seperator) . "\n";
    }
    return $res;
}

////////////////////////////////////////////////////////////////////////
/**
* Returns the data for one table
*
* @access   public
*
* @param    AbstractDB  $db                 db identifier
* @param    String      $table              table name
* @param    Integer     $type               ANYDB_DUMP_ constants
* @param    String      $seperator          for csv files
*
* @returns  Array       the table data
*/
function getTable(& $db, $table, $type = ANYDB_DUMP_SQL, $seperator = "\t") {
    $res = '';
    $first = true;
    // get all the data
    $query = "SELECT * FROM $table";
    $db->query($query, ANYDB_RES_ASSOC);
    while ($line = $db->getNext()) {
        $line = $db->escapeStr($line);
        switch ($type) {
            case ANYDB_DUMP_SQL:
                $res .= QueryHelper::insert($table, $line) . ";\n";
                break;
            case ANYDB_DUMP_CSV:
                if ($first) {
                    $res .= implode($seperator, array_keys($line))  . "\n";
                    $first = false;
                }
                $res .= implode($seperator, $line) . "\n";
                break;
            }
    }
    return $res;
}
////////////////////////////////////////////////////////////////////////
}
////////////////////////////////////////////////////////////////////////

?>
