<?php
////////////////////////////////////////////////////////////////////////
/**
* Widget class for anyDB
*
* With this class you can display db data on serveral html pages.
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

class PageWidget {


////////////////////////////////////////////////////////////////////////
/*
	function PageWidget(& $db, $function, $table, $entries) 
	function nextExists() 
	function prevExists() 
	function pageExists($page) 
	function gotoPage($page) 
   function getIndices()
   function orderBy($orderID, $ascend)
	function get($array = null) 
	function getIndex($separator = ' - ', $makeBold = true) 
	function getPrevLink($title = 'Previous', $alwaysDisplay = true) 
	function getNextLink($title = 'Next', $alwaysDisplay = true) 
	function getPageDropdown() 
	function getOrderDropdown($array = null) 
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

// public vars

var $total;
var $entries;
var $page = -1;
var $pages = 1;
var $start = 0;
var $end = 0;
var $orderedBy;
var $ascend = true;

// private vars

var $_db = null;
var $_table;
var $_queryFunction = null;

////////////////////////////////////////////////////////////////////////
// constructor
// needs a db object, a function for the 'select limit'  statement, 
// the table and the number of entries

function PageWidget(& $db, $function, $table, $entries) {
	global $HTTP_GET_VARS;
	$this->_db = $db;
	$this->_table = $table;
	$this->entries = $entries;
	if (@$HTTP_GET_VARS['entries']) {
		$this->entries = $HTTP_GET_VARS['entries'];
	}
	$this->_queryFunction = $function;
	$this->_db->query("select count(*) from $this->_table") 
		or die ($db->error);
	$this->total = $this->_db->getValue();
	$this->pages = (int)($this->total / $entries) + (($this->total % $entries) != 0 ? 1 : 0);
	if ($this->total > 0) {
		if ($this->pages == 0) {
			if ($this->total > 0) {
				$this->pages++;
			}
		}
		$this->_db->query("select * from $this->_table");
		$keys = array_keys($this->_db->getNext());
		$this->_columns = array_merge(array('--none--'), $keys);
		$this->orderedBy = $keys[0];
	}

	$page = 1;
	if (@$HTTP_GET_VARS['page']) {
		$page = $HTTP_GET_VARS['page'];
	}
	$this->gotoPage($page);
	if (@$HTTP_GET_VARS['orderid']) {
		$orderId = $HTTP_GET_VARS['orderid'];
		$ascend = $HTTP_GET_VARS['ascend'];
		$this->orderBy($orderId, $ascend);
	}
}

////////////////////////////////////////////////////////////////////////
// does the next page exist

function nextExists() {
	return (($this->entries * $this->page) < $this->total);
}

////////////////////////////////////////////////////////////////////////
// does the previous page exist

function prevExists() {
	return ($this->page - 1 > 0);
}

////////////////////////////////////////////////////////////////////////
// does the page number exist

function pageExists($page) {
	if ($page > 0) {
		return (($this->entries * ($page - 1)) < $this->total);
	} 
	return false;
}

////////////////////////////////////////////////////////////////////////
// get the submitted page number or sets the page passed as a parameter

function gotoPage($page) {
	if ($page != $this->page) {
		$this->page = $page;
		$this->start = ($this->page  - 1)* $this->entries + 1;
		$this->end = ($this->page)* $this->entries;
		if ($this->end > $this->total) {
			$this->end = $this->total;
		}
	return true;
	}
	return false;
}

////////////////////////////////////////////////////////////////////////
// orders the result set

function orderBy($orderID, $ascend) {
	if ($orderID != $this->orderedBy) {
		if ($orderID == '--none--') {
			$this->orderedBy = '';
		} else {
			if (@array_search($orderID, $this->_columns)) {
				$this->orderedBy = $orderID;
				$this->ascend = $ascend;
			} 
			return true;
		}
		return false;
	}
}

////////////////////////////////////////////////////////////////////////
// returns an array with the result data for the selected page

function get($array = null) {
	$offset = (int)$this->entries * ((int)$this->page - 1);
	if (@is_array($array)) {
		$what = implode (', ', $array);
	} else {
		$what = '*';
	}
	$str = $this->_callFunction ($this->_queryFunction, true, $what, $this->_table, $offset, $this->entries, $this->orderedBy, $this->ascend);
	$this->_db->query($str);
	echo $this->_db->error;
	return $this->_db->getAll();
}
////////////////////////////////////////////////////////////////////////
// returns the numbered index

function getIndex($separator = ' - ', $makeBold = true, $linkAddon = array()) {
	$str = '';
	global $HTTP_SERVER_VARS;
	$self = basename($HTTP_SERVER_VARS['PHP_SELF']);
	for ($i=1; $i <= $this->pages; $i++) {
		$url = $self . "?page=$i&amp;entries=".$this->entries.($this->orderedBy != '' ? '&amp;orderid=' . $this->orderedBy . '&amp;ascend=' . $this->ascend : '');
      foreach ($linkAddon as $key => $val) {
         $url .= "&amp;$key=$val";
      }
		$str .= ($i == $this->page ? ($makeBold ?  "<b>$i</b>" : $i) : "<a href=\"$url\">$i</a>") . $separator;
	}
	$str = substr($str, 0, strlen($separator) * (-1));
	return $str;		
}
////////////////////////////////////////////////////////////////////////
// returns the 'previous' link

function getPrevLink($title = 'Previous', $alwaysDisplay = true, $linkAddon = array()) {
	global $HTTP_SERVER_VARS;
	$self = basename($HTTP_SERVER_VARS['PHP_SELF']);
	if ($this->prevExists()) {
		$url = $self . "?page=" . ($this->page  - 1) ."&amp;entries=".$this->entries. ($this->orderedBy != '' ? '&amp;orderid=' . $this->orderedBy  . '&amp;ascend=' . $this->ascend : '');
      foreach ($linkAddon as $key => $val) {
         $url .= "&amp;$key=$val";
      }
		return "<a href=\"$url\">$title</a>";
	}
	if ($alwaysDisplay) {
	   return $title;
	}
}

////////////////////////////////////////////////////////////////////////
// returns the 'next' link

function getNextLink($title = 'Next', $alwaysDisplay = true, $linkAddon = array()) {
	global $HTTP_SERVER_VARS;
	$self = basename($HTTP_SERVER_VARS['PHP_SELF']);
	if ($this->nextExists()) {
		$url = $self . "?page=" . ($this->page  + 1) ."&amp;entries=".$this->entries. ($this->orderedBy != '' ? '&amp;orderid=' . $this->orderedBy  . '&amp;ascend=' . $this->ascend : '');
      foreach ($linkAddon as $key => $val) {
         $url .= "&amp;$key=$val";
      }
      return "<a href=\"$url\">$title</a>";
	}
	if ($alwaysDisplay) {
	   return $title;
	}
}

////////////////////////////////////////////////////////////////////////
// creates a dropdown list for jumping to pages

function getPageDropdown() {
	global $HTTP_SERVER_VARS;
	$self = basename($HTTP_SERVER_VARS['PHP_SELF']);
	$res = '';
	$res .= "<form name=\"PageIt\" action=\"$self\" method=\"GET\">";
	$res .= "<select name=\"page\" size=\"1\" onChange=\"document.PageIt.submit()\">\n";
	for ($i=1; $i <= $this->pages; $i++) {
		$res .= "<option" . ($this->page == $i ? " selected=\"selected\"" : '') . ">$i</option>\n";
	}
	$res .= "<input type=\"hidden\" name=\"orderid\" value=\"$this->orderedBy\">";
	$res .= "<input type=\"hidden\" name=\"ascend\" value=\"$this->ascend\">";
	$res .="</select></form>\n";
	return $res;					
}

////////////////////////////////////////////////////////////////////////
// creates a dropdown list for ordering pages

function getOrderDropdown($array = null) {
	if ($array == null) {
		$array = & $this->_columns;
	} else {
		$array = array_merge(array('--none--'), $array);
	}
	global $HTTP_SERVER_VARS;
	$self = basename($HTTP_SERVER_VARS['PHP_SELF']);
	$res = '';
	$res .= "<form name=\"OrderIt\" action=\"$self\" method=\"GET\">";
	$res .= "<select name=\"orderid\" size=\"1\" onChange=\"document.OrderIt.submit()\">\n";
	$size = sizeof($array);
	for ($i=0; $i < $size; $i++) {
		$res .= "<option" . ($this->orderedBy == $array[$i] ? " selected=\"selected\"" : '') . ">" . $array[$i]. "</option>\n";
	}
	$res .= "<input type=\"hidden\" name=\"page\" value=\"1\">";
	$res .= "<input type=\"hidden\" name=\"ascend\" value=\"$this->ascend\">";
	$res .= "<input type=\"hidden\" name=\"entries\" value=\"$this->entries\">";
	$res .="</select></form>\n";
	return $res;					
}

////////////////////////////////////////////////////////////////////////
// returns the query indices in the correct order

function getIndices() {
    $res = array();
    if ($this->ascend) {
        $start = $this->start;
        $end = $this->end;
    } else {
        $start = $this->total - $this->end + 1;
        $end = $this->total - $this->end + ($this->end - $this->start) + 1;
    }
    for ($i=$start; $i <= $end; $i++) {
        array_push($res, $i);
    }
    if ($this->ascend) {
        return $res;
    } else {
        return array_reverse($res);
    }
}

////////////////////////////////////////////////////////////////////////
// 10-22-02
/**
* Checks if a function exists an calls it
*
* @access   private
*
* @param    String  $functionName
* @param    boolean $abortAmbiguous  
* @param    mixed   the params for the function
*
* @return   Integer    -1 when an error occurs or the retrun value of the function
*/

function _callFunction($functionName, $abortAmbiguous = false) {
        // # of params
        $count = func_num_args();
        // array for passed on params
        $params = array();
        for ($i=2; $i < $count; $i++) {
            $params[$i-1] = func_get_arg($i);
        }
        // flags if funcrion exists in a class or outside
        $isInside = method_exists(@$this, $functionName);
        $isOutside = function_exists($functionName);
        // do we need to abort if function name is ambigous?
        if ($abortAmbiguous) {
            if ($isInside && $isOutside) {
                return -1;
            }
        } 
        // call the inner method first
        if ($isInside) {
            return call_user_func_array(array($this, $functionName), $params);
        // or the "outer" one
        } else if ($isOutside) {
            return call_user_func_array($functionName, $params);
        // function does not exist at all
        } else if ($functionName) {
            return -1;
        }
}
////////////////////////////////////////////////////////////////////////

}
////////////////////////////////////////////////////////////////////////
// END OF CLASS
////////////////////////////////////////////////////////////////////////
// db specfic functions for querying the limited number of data

function mysqlLimitQuery($what, $table, $offset, $count, $orderBy = '', $ascend = true) {
	$query = "SELECT %s FROM %s" . ($orderBy != '' ? " ORDER BY $orderBy " . ($ascend ? '' : 'DESC') : '') ." LIMIT %s, %s";
	return sprintf($query, $what, $table, $offset, $count);
}

////////////////////////////////////////////////////////////////////////

function sqliteLimitQuery($what, $table, $offset, $count, $orderBy = '', $ascend = true) {
	$query = "SELECT %s FROM %s" . ($orderBy != '' ? " ORDER BY $orderBy " . ($ascend ? '' : 'DESC') : '') ." LIMIT %s, %s";
	return sprintf($query, $what, $table, $offset, $count);
}

////////////////////////////////////////////////////////////////////////

function postgresLimitQuery($what, $table, $offset, $count, $orderBy = '', $ascend = true) {
	$query = "SELECT %s FROM %s" . ($orderBy != '' ? " ORDER BY $orderBy " . ($ascend ? '' : 'DESC') : '') ." LIMIT %s OFFSET %s";
	return sprintf($query, $what, $table, $count, $offset);
}

////////////////////////////////////////////////////////////////////////

function dbxLimitQuery($what, $table, $offset, $count, $orderBy = '', $ascend = true) {
	$query = "SELECT %s FROM %s" . ($orderBy != '' ? " ORDER BY $orderBy " . ($ascend ? '' : 'DESC') : '') ." LIMIT %s,%s";
	return sprintf($query, $what, $table, $offset, $count);
}

////////////////////////////////////////////////////////////////////////

function accessLimitQuery($what, $table, $offset, $count, $orderBy ='', $ascend = true) {
	$query = "SELECT $what FROM (SELECT TOP $count $what FROM (SELECT TOP ".($count + $offset)." $what FROM $table".($orderBy != '' ? " ORDER BY $orderBy ".($ascend ? 'ASC' : 'DESC') : '').")".($orderBy != '' ? " ORDER BY $orderBy ".($ascend ? 'DESC' : 'ASC') : '').")".($orderBy != '' ? " ORDER BY $orderBy " . ($ascend ? 'ASC' : 'DESC') : '');
	return $query;
}

////////////////////////////////////////////////////////////////////////
?>
