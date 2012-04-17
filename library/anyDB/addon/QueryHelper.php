<?php
////////////////////////////////////////////////////////////////////////

require_once dirname(__FILE__) . '/../base/UtilityClass.php';

////////////////////////////////////////////////////////////////////////

/**
* Utility class for anydB
*
* This class provides methods to generate sql queries.
*
* Don't instanciate this class.<br>
* Use 'QueryHelper::methodName()' instead.
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
	
class QueryHelper extends UtilityClass {

////////////////////////////////////////////////////////////////////////
/*
    function update($table, $values, $where = '', $smartQuotes=true, $addon = '', $statementAddon = '') 
    function insert($table, $values, $smartQuotes = true, $addon = '', $statementAddon = '') 
    function replace($table, $values, $smartQuotes = true, $addon = '', $statementAddon = '') 
    function select($columns, $tables, $where = '', $smartQuotes = true, $addon = '', $statementAddon = '') 
    function delete($table, $where = '', $smartQuotes = true, $addon = '', $statementAddon = '') 
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
* Returns an sql UPDATE query
*
* @access   public
* @static
*
* @param    String      $table              the table
* @param    Mixed       $values             the values to be changed as an associative array as a string
* @param    String      $where              where clause
* @param    String      $addon              inserted at the end
* @param    String      $statementAddon     inserted after the UPDATE keyword
*
* @returns  String      sql query
*/
function update($table, $values, $where = '', $smartQuotes=true, $addon = '', $statementAddon = '') {
    $str = 'UPDATE %s %s SET %s %s %s';
	// array?	
	if (@is_array($values)) {
		$vals = '';
		// generate assignments
    		foreach($values as $key => $val) {
        		if ($smartQuotes) {
        			if (is_int($val)) {
        			    $vals .= "$key=$val, ";
                    } else {
                            $vals .= "$key='$val', ";
        			}
                } else {
                        $vals .= "$key=$val, ";
                }
            }
		$vals = substr($vals, 0, -2);
	// single string
	} else {
		$vals = $values;
	}
	// build query
    $query = sprintf($str, $statementAddon, $table, $vals, QueryHelper::_where($where, $smartQuotes), $addon);
    return $query;
}

////////////////////////////////////////////////////////////////////////
/**
* Returns an sql INSERT query
*
* @access   public
* @static
*
* @param    String      $table              the table
* @param    Mixed       $values             the values to be changed as an associative array as a string
* @param    String      $smartQuotes        wrap Strings in quotes?
* @param    String      $addon              inserted at the end
* @param    String      $statementAddon     inserted after the INSERT keyword
*
* @returns  String      sql query
*/
function insert($table, $values, $smartQuotes = true, $addon = '', $statementAddon = '') {
    $str = 'INSERT %s INTO %s %s VALUES (%s) %s';
    $cols = '';
    if (@is_array($values)) {
        $vals = '';
        foreach($values as $val) {
    		if ($smartQuotes) {
    		    if (is_int($val)) {
                    $vals .= "$val, ";
                } else {
                    $vals .= "'$val', ";
                }
            } else {
                $vals .= "$val, ";
            }
        }
		$vals = substr($vals, 0, -2);

        if (!QueryHelper::_onlyNumericKeys($values)) {
            $cols = '(' . implode(', ', array_keys($values)) . ')';
        }
    } else {
        $vals = $values;
    }
    $query = sprintf($str, $statementAddon, $table, $cols, $vals, $addon);
    return $query;
}

////////////////////////////////////////////////////////////////////////
/**
* Returns an sql REPLACE query
*
* @access   public
* @static
*
* @param    String      $table              the table
* @param    Mixed       $values             the values to be changed as an associative array as a string
* @param    String      $smartQuotes        wrap Strings in quotes?
* @param    String      $addon              inserted at the end
* @param    String      $statementAddon     inserted after the REPLACE keyword
*
* @returns  String      sql query
*/
function replace($table, $values, $smartQuotes = true, $addon = '', $statementAddon = '') {
    $str = 'REPLACE %s INTO %s %s VALUES (%s) %s';
    $cols = '';
    if (@is_array($values)) {
        $vals = '';
        foreach($values as $val) {
    		if ($smartQuotes) {
    		    if (is_int($val)) {
                    $vals .= "$val, ";
                } else {
                    $vals .= "'$val', ";
                }
            } else {
                $vals .= "$val, ";
            }
        }
		$vals = substr($vals, 0, -2);

        if (!QueryHelper::_onlyNumericKeys($values)) {
            $cols = '(' . implode(', ', array_keys($values)) . ')';
        }
    } else {
        $vals = $values;
    }
    $query = sprintf($str, $statementAddon, $table, $cols, $vals, $addon);
    return $query;
}
////////////////////////////////////////////////////////////////////////
/**
* Returns an sql SELECT query
*
* @access   public
* @static
*
* @param    Mixed       $columns            the columns
* @param    String      $table              the table
* @param    String      $where              where clause
* @param    String      $smartQuotes        wrap Strings in quotes?
* @param    String      $addon              inserted at the end
* @param    String      $statementAddon     inserted after the SELECT keyword
*
* @returns  String      sql query
*/
function select($columns, $tables, $where = '', $smartQuotes = true, $addon = '', $statementAddon = '') {
    $str = 'SELECT %s %s FROM %s %s %s';
    $cols = QueryHelper::_getCommaSep($columns);
    $tabs = QueryHelper::_getCommaSep($tables);

    $query = sprintf($str, $statementAddon, $cols, $tabs, QueryHelper::_where($where, $smartQuotes,' AND') ,$addon);
    return $query;
}

////////////////////////////////////////////////////////////////////////
/**
* Returns an sql DELETE query
*
* @access   public
* @static
*
* @param    String      $table              the table
* @param    String      $where              where clause
* @param    String      $smartQuotes        wrap Strings in quotes?
* @param    String      $addon              inserted at the end
* @param    String      $statementAddon     inserted after the DELETE keyword
*
* @returns  String      sql query
*/

function delete($table, $where = '', $smartQuotes = true, $addon = '', $statementAddon = '') {
    $str = 'DELETE %s FROM %s %s %s';
    $query = sprintf($str, $statementAddon, $table, QueryHelper::_where($where, $smartQuotes, ' AND '), $addon);
    return $query;
}

////////////////////////////////////////////////////////////////////////
//PRIVATE
////////////////////////////////////////////////////////////////////////
/**
* Comma seperates an array
*
* @access   private
* @static
*
* @param    Mixed       $var                Array or String
*
* @returns  String      the comma separated string
*/
function _getCommaSep($var) {
    if (@is_array($var)) {
        return implode(', ', $var);
    } else {
        return $var;
    }
}

////////////////////////////////////////////////////////////////////////
/**
* Checks if array has only numeric keys
*
* @access   private
* @static
*
* @param    Array       $array             
*
* @returns  Boolean
*/

function _onlyNumericKeys($array) {
     if(@is_array($array)) {
        foreach ($array as $key => $tmp) {
            if (!is_numeric($key)) {
               return false;                
            }
        }
        return true;
     }
     return null;
}

////////////////////////////////////////////////////////////////////////
/**
* Builds the where clause
*
* @access   private
* @static
*
* @param    Mixed       $where
* @param    Boolean     $smartQuotes
*
* @returns  String
*/
function _where($where, $smartQuotes = true, $separator = ',') {
    if (is_array($where)) {
            $vals = '';
    		foreach($where as $key => $val) {
        		if ($smartQuotes) {
        			if (is_int($val)) {
        			    $vals .= "$key=$val$separator ";
                    } else {
                            $vals .= "$key='$val'$separator ";
        			}
                } else {
                        $vals .= "$key=$val$separator ";
                }
            }
		$len = (strlen($separator) + 1)*(-1);
		$vals = substr($vals, 0, $len);

    return  'WHERE ' . $vals;


    } else {
        if ($where != '') {
            return 'WHERE ' . QueryHelper::_getCommaSep($where);
        }
    }
}

////////////////////////////////////////////////////////////////////////
}
////////////////////////////////////////////////////////////////////////
/*
echo QueryHelper::insert('peter', array('a',4,2)) . '<br>';
echo QueryHelper::insert('peter', array('id' => 2,'name' =>'peter')) . '<br>';
echo QueryHelper::insert('peter', array('id' => 2,'name' =>"'peter'"), false) . '<br>';

echo QueryHelper::delete('peter', array('id'=>2,'name' =>'peter')) . '<br>';
echo QueryHelper::replace('peter', array('id' => 2,'name' =>'peter')) . '<br>';

echo QueryHelper::select(array('name', 'test'), 'peter', array('id' =>5,'test'=>'2'), true, 'LIMIT 10', 'DISTINCT') . '<br>';
echo QueryHelper::select(array('name', 'test'), 'peter', 'id=3', false, 'LIMIT 10') . '<br>';

echo QueryHelper::update('peter', array('name' =>'peter', 'id'=>4), 'id=10') . '<br>';
echo QueryHelper::update('peter', array('name' =>'peter', 'id'=>2), 'id=10', false) . '<br>';

*/
////////////////////////////////////////////////////////////////////////
?>