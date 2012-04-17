<?php
////////////////////////////////////////////////////////////////////////

require_once dirname(__FILE__) . '/../base/UtilityClass.php';

////////////////////////////////////////////////////////////////////////
/**
* Utility class for anyDB
*
* This class provides methods to display db results and functions for common sql queries.<br>
*
* Don't instanciate this class.<br>
* Use 'DBHelper::methodName()' instead.
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

class DBHelper extends UtilityClass {

////////////////////////////////////////////////////////////////////////
/*
   function cloneDataset(&$db, $table, $id_col, $id, $exclusion = null) {

   function dumpAll($array, $addHeader=true, $headerArray = null, $tableTag = 'border="1"', $trTag = '', $tdTag = '', $thTag = '') {
   function dumpColumn($array, $horizontal = true, $headerTitle = '', $tableTag = 'border="1"', $trTag = '', $tdTag = '', $thTag = '') {
   function dumpNext($array, $addHeader = false, $singleRow = true, $headerTitle = '', $tableTag = 'border="1"', $trTag = '', $tdTag = '', $thTag = '') {

   function selectBox($array, $name='', $selected=0, $size=1, $multiple=false, $additional='') {
   function checkBoxes($array, $name='', $selected=null, $additional='') {
   function radioButtons($array, $name='', $selected=0, $additional='') {

   function getMin(& $db, $tableName, $columnName) {
   function getMax(& $db, $tableName, $columnName) {
   function getCount(& $db, $tableName) {
*/
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
* Returns the resulting table but uses the $key field as an index
*
* @abstract 
* @access   public
*
* @param    String     $table        the table name
* @param    String     $id_col       the name of the primary key column
* @param    String     $id           the key of the dataset to clone
* @param    Array      $exclusion    columns not to copy
*
* @return   Mixed       new key or FALSE
*/
function cloneDataset(&$db, $table, $id_col, $id, $exclusion = null) {
   $db->query("SELECT * FROM $table WHERE $id_col='$id'");
   $data = $db->getNext();
   if ($data) {
      unset($data[$id_col]);
      if (is_array($exclusion)) {
         foreach ($exclusion as $key) {
            unset($data[$key]);
         }
      }
      if ($db->query(QueryHelper::insert($table, $data))) {
         $res = $db->getInsertId();
         if (!$res) {
            $db->query(QueryHelper::select($id_col, $table, $data));
            return $db->getValue();
         }
         return $res;
      }
   }
   echo $db->lastQuery;
   return false;
}

////////////////////////////////////////////////////////////////////////
/**
* Returns a html table for a 2-dimensional result set
*
* @access   public
* @static
*
* @param    Array       $array              the result set
* @param    Boolean     $addHeader          show the array keys as header
* @param    Boolean     $headerArray        alternative headers
*
* @returns  String      html source
*/
function dumpAll($array, $addHeader=true, $headerArray = null, $tableTag = 'border="1"', $trTag = '', $tdTag = '', $thTag = '') {
    $res = '';
    if (@is_array($array) && @sizeof($array)) {
        $res .= "<table".($tableTag ? " $tableTag" : '').">";
			// add header?
            if ($addHeader) {
                $res .= "<tr><th".($thTag ? " $thTag" : '').">" . @implode("</th><th".($thTag ? " $thTag" : '').">",  ($headerArray != null ? $headerArray : array_keys($array[0]))) . "</th></tr>";
            }
        foreach(@$array as $values) {
			// check if array is 2dim
			if (@!is_array($values)) {
				return false;
			}
            $res .= "<tr".($trTag ? " $trTag" : '').">";
            foreach($values as $key => $value) {
                $res .= "<td".($tdTag ? " $tdTag" : '').">" . ($value != '' ? $value : '') . "</td>";
            }
            $res .= "</tr>";
        }
        $res .= "</table>";
    }
    return $res;
}

////////////////////////////////////////////////////////////////////////
/**
* Returns a html table for a result column
*
* @access   public
* @static
*
* @param    Array       $array              the result set
* @param    Boolean     $horizontal         order the table horizontally?
* @param    Boolean     $headerTitle        alternative header?
*
* @returns  String      html source
*/
function dumpColumn($array, $horizontal = true, $headerTitle = '', $tableTag = 'border="1"', $trTag = '', $tdTag = '', $thTag = '') {
    $res = '';
    if (@is_array($array) && @sizeof($array)) {
        $res .= "<table".($tableTag ? " $tableTag" : '').">";
        $res .= "<tr $trTag>";
        if ($headerTitle != '') {
            $res .= "<th".($thTag ? " $thTag" : '').">$headerTitle</th>" . (!$horizontal ? "</tr>" : '');
        }
        foreach($array as $key => $value) {
            if (@is_array($value)) {
                return '';
            }
            $res .= (!$horizontal ? '<tr'.($trTag ? " $trTag" : '').'>' : '') . "<td".($tdTag ? " $tdTag" : '').">" . ($value != '' ? $value : '') . "</td>" . (!$horizontal ? "</tr>" : '');
        }
        if ($horizontal) {
            $res .= "</tr>";
        }
    $res .= "</table>";
    }
    return $res;
}
////////////////////////////////////////////////////////////////////////
/**
* Returns a html table for a result row
*
* @access   public
* @static
*
* @param    Array       $array              the result set
* @param    Boolean     $addHeader          display keys as header?
* @param    Boolean     $singleRow          append table open and close tags?
*
* @returns  String      html source
*/
function dumpNext($array, $addHeader = false, $singleRow = true, $headerTitle = '', $tableTag = 'border="1"', $trTag = '', $tdTag = '', $thTag = '') {
    $res = '';
    if (@is_array($array) && @sizeof($array)) {
        if ($singleRow) {
	        $res .= "<table".($tableTag ? " $tableTag" : '').">";
	        foreach($array as $key => $value) {
	        	if (@is_array($value)) {
	        		return '';
	        	}
	            $res .= "<tr".($trTag ? " $trTag" : '').">" . ($addHeader ? "<th".($thTag ? " $thTag" : '').">$key</th>" : '') . "<td".($tdTag ? " $tdTag" : '').">" . ($value != '' ? $value : '') . "</td></tr>";
	        }
	    $res .= "</table>";
		} else {
			if ($addHeader) {
                $res .= "<tr".($trTag ? " $trTag" : '')."><th>" . implode("</th><th".($thTag ? " $thTag" : '').">", array_keys($array)) . "</th></tr><tr>";
			}
			$res .= "<td".($tdTag ? " $tdTag" : '').">" . implode($array, "</td><td>") .'</td>';
		}
	}
    return $res;
}
////////////////////////////////////////////////////////////////////////
/**
* Returns a html selectBox for a result column
*
* @access   public
* @static
*
* @param    Array       $array              the result set
* @param    Integer     $selected           the preselected value
* @param    Integer     $size               number of elements shown
* @param    Boolean     $multiple           multiple select?
* @param    String      $additional         additional html code for the select tag
*
* @returns  String      html source
*/
function selectBox($array, $name='', $selected=0, $size=1, $multiple=false, $additional='') {
    $res = '';
    static $count = 0;
    if (is_array($array)) {
        if ($name == '') {
            $name = 'selectBox' . ++$count;
        }
        $res .= "<select name=\"$name\" size=\"$size\"" . ($multiple==false ? '' : " multiple=\"multiple\"") . ($additional ? " $additional" : '') . ">";
        $i = 0;
        foreach($array as $i => $value) {
        	if (is_array($value)) {
        	   $k = array_keys($value);
        	   $descr = $value[$k[0]];
        	   $val = $value[$k[1]];
        	} else {
            $val = $descr = $value;
        	}
         @$res .= "<option" . ($selected == $i? " selected=\"selected\"" : '') . " value=\"$val\">$descr</option>\n";
        }
        $res .="</select>";
    }
    return $res;
}

////////////////////////////////////////////////////////////////////////
// note:
// if array is a 2col. array -> selected[] refers to the values
// if array is a 1col. array -> selected[] refers to the position in the array

function checkBoxes($array, $name='', $selected=null, $additional='') {
    $res = '';
    static $count = 0;
    if (is_array($array)) {
        if ($name == '') {
            $name = 'checkboxes' . ++$count;
        }
        $i = 0;
        foreach($array as $i => $value) {
        	if (is_array($value)) {
        	   $k = array_keys($value);
        	   $descr = $value[$k[0]];
        	   $val = $value[$k[1]];
        	} else {
            $val = $descr = $value;
        	}
        	   $sel = false;
        	   if (is_array($selected)) {
     	         $comp = (is_array($value) ? $val : ($i + 1));
        	      $sel = in_array($comp, $selected);
        	   } else {
        	      $sel = ($selected == ($i + 1));
        	   }
            @$res .= "<input name=\"$name"."[]\" type=\"checkbox\" " . ($sel ? " checked=\"checked\"" : '') . " value=\"$val\"" . $additional . "> $descr</br>";
        }
    }
    return $res;
}

////////////////////////////////////////////////////////////////////////

function radioButtons($array, $name='', $selected=0, $additional='') {
    $res = '';
    static $count = 0;
    if (is_array($array)) {
        if ($name == '') {
            $name = 'radiobuttons' . ++$count;
        }
        $i = 0;
        foreach($array as $i => $value) {
        	if (is_array($value)) {
        	   $k = array_keys($value);
        	   $descr = $value[$k[0]];
        	   $val = $value[$k[1]];
        	} else {
            $val = $descr = $value;
        	}
            @$res .= "<input  name=\"$name\" type=\"radio\" " . ($selected == $i ? " checked=\"checked\"" : '') . " value=\"$val\"" . $additional . "> $descr</br>";
        }
    }
    return $res;
}

////////////////////////////////////////////////////////////////////////
/**
* Returns the min value for a given table column
*
* @access   public
* @static
*
* @param    AbstractDB  $db                 db resource
* @param    String      $tableName          table
* @param    String      $columnName         column
*
* @returns  String      value
*/
function getMin(& $db, $tableName, $columnName) {
    $db->query("SELECT min($columnName) FROM $tableName");
    return  $db->getValue();
}

////////////////////////////////////////////////////////////////////////
/**
* Returns the max value for a given table column
*
* @access   public
* @static
*
* @param    AbstractDB  $db                 db resource
* @param    String      $tableName          table
* @param    String      $columnName         column
*
* @returns  String      value
*/

function getMax(& $db, $tableName, $columnName) {
    $db->query("SELECT max($columnName) FROM $tableName");
    return  $db->getValue();
}

////////////////////////////////////////////////////////////////////////
/**
* Returns the number of elements in a column
*
* @access   public
* @static
*
* @param    AbstractDB  $db                 db resource
* @param    String      $tableName          table
*
* @returns  Integer     value
*/
function getCount(& $db, $tableName) {
    $db->query("SELECT count(*) FROM $tableName");
    return  $db->getValue();
}

////////////////////////////////////////////////////////////////////////
}
////////////////////////////////////////////////////////////////////////
?>