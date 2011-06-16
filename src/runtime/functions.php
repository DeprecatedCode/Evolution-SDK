<?php

function is_empty($value) {
	if(!$value || $value == array())
		return true;
	if(is_array($value)) {
		foreach($value as $next) {
			if(!is_empty($next))
				return false;
		}
		return true;
	}
}

function describe(&$object) {
	$class = get_class($object);
	return "[$class]";
}

function add_message($type, $msg) {
	$m = array('type' => $type, 'message' => $msg);
	
	if(!is_array($GLOBALS['add_messages']))
		$GLOBALS['add_messages'] = array();
		
	$GLOBALS['add_messages'][] = $m;
	
	$rdata = e::$session->flashdata('result_data');
	
	if(!is_array($rdata))
		$rdata = array();
	
	if(!is_array($rdata['messages']))
		$rdata['messages'] = array();

	foreach($GLOBALS['add_messages'] as $message)
		$rdata['messages'][] = $message;
		
	e::$session->flashdata('result_data', $rdata);
	
	return true;
}

function array_squeeze($array, $prefix = '', $char = '.') {
	$new = array();
	foreach($array as $key => $value) {
		if(is_array($value)) {
			$sub = array_squeeze($value, $prefix . $key . $char);
			$new = array_merge($new, $sub);
		} else {
			$new[$prefix . $key] = $value;
		}
	}
	return $new;
}

function array_bulge($array, $base = array(), $char = '.') {
	foreach($array as $key => $value) {
		$path = explode($char, $key, 2);
		if(count($path) == 1)
			$base[$path[0]] = $value;
		else {
			if(!isset($base[$path[0]]))
				$base[$path[0]] = array();
			$base[$path[0]] = array_bulge(array($path[1] => $value), $base[$path[0]]);
		}
	}
	return $base;
}
	
function langSort($a, $b) {
	if($a == $b)
		return 0;
	if($a == 'title')
		return -1;
	if($b == 'title')
		return 1;
	if($a == 'template')
		return -1;
	if($b == 'template')
		return 1;
	return strcasecmp($a, $b);
}

function lang_sort($lang) {
	$out = array();
	
	uksort($lang, "langSort");
	foreach($lang as $key => $value) {
		if(is_array($value))
			$value = lang_sort($value);
		$out[$key] = $value;
	}
	return $out;
}

function force_file_put_contents($file, $contents) {
	$dir = dirname($file);
	$name = basename($file);
	$r = true;
	if(!is_dir($dir))
		$r = mkdir($dir, 0, true);
		
	if(!$r)
		s('Create directory failed at ' . $dir . ' (it is recursive, so either create a directory at the top with write permissions or let the deepest existing dir have write permissions)');
		
	return file_put_contents($file, $contents);
}

function encode64($s) {
	return str_replace('=', '_', base64_encode($s));
}

function decode64($s) {
	return base64_decode(str_replace('_', '=', $s));
}

function first_day_of_month(){
	$cur=date('Y-m-d');
	$datetest1=date('Y-m-d',strtotime('next wednesday',strtotime(date("Y-m-01",strtotime("now")))));
	$datetest2=date('Y-m-d',strtotime('next wednesday',strtotime(date("Y-m-01",strtotime("next month")))));
	
	if($cur<=$datetest1){
		return $datetest1;
	}else{
		return $datetest2;
	}
}

function is_image($file_type){
	$filetype=explode('/',$file_type);
	return ($filetype[0]=='image');
}

function clear_cache($file_str=false){
	if(!$file_str){
		$files=scandir(ROOT.'/static/cache/thumbnails');
		foreach($files as $file){
			if(($file!=".") && ($file!=".."))unlink(ROOT."/static/cache/thumbnails/$file");
		}
	}else{
		$files=scandir(ROOT.'/static/cache/thumbnails');
		foreach($files as $file){
			if(strstr($file,$file_str))unlink(ROOT."/static/cache/thumbnails/$file");
		}
	}
}

// PHP String Replace First (from stackoverflow)
function str_replace_first($search, $replace, $subject) {
    $pos = strpos($subject, $search);
    if ($pos !== false) {
        $subject = substr_replace($subject, $replace, $pos, strlen($search));
    }
    return $subject;
}


// Extend PHP with a PECL function
	if (!function_exists('http_build_url'))
	{
		define('HTTP_URL_REPLACE', 1);				// Replace every part of the first URL when there's one of the second URL
		define('HTTP_URL_JOIN_PATH', 2);			// Join relative paths
		define('HTTP_URL_JOIN_QUERY', 4);			// Join query strings
		define('HTTP_URL_STRIP_USER', 8);			// Strip any user authentication information
		define('HTTP_URL_STRIP_PASS', 16);			// Strip any password authentication information
		define('HTTP_URL_STRIP_AUTH', 32);			// Strip any authentication information
		define('HTTP_URL_STRIP_PORT', 64);			// Strip explicit port numbers
		define('HTTP_URL_STRIP_PATH', 128);			// Strip complete path
		define('HTTP_URL_STRIP_QUERY', 256);		// Strip query string
		define('HTTP_URL_STRIP_FRAGMENT', 512);		// Strip any fragments (#identifier)
		define('HTTP_URL_STRIP_ALL', 1024);			// Strip anything but scheme and host
		
		// Build an URL
		// The parts of the second URL will be merged into the first according to the flags argument. 
		// 
		// @param	mixed			(Part(s) of) an URL in form of a string or associative array like parse_url() returns
		// @param	mixed			Same as the first argument
		// @param	int				A bitmask of binary or'ed HTTP_URL constants (Optional)HTTP_URL_REPLACE is the default
		// @param	array			If set, it will be filled with the parts of the composed url like parse_url() would return 
		function http_build_url($url, $parts=array(), $flags=HTTP_URL_REPLACE, &$new_url=false)
		{
			$keys = array('user','pass','port','path','query','fragment');
			
			// HTTP_URL_STRIP_ALL becomes all the HTTP_URL_STRIP_Xs
			if ($flags & HTTP_URL_STRIP_ALL)
			{
				$flags |= HTTP_URL_STRIP_USER;
				$flags |= HTTP_URL_STRIP_PASS;
				$flags |= HTTP_URL_STRIP_PORT;
				$flags |= HTTP_URL_STRIP_PATH;
				$flags |= HTTP_URL_STRIP_QUERY;
				$flags |= HTTP_URL_STRIP_FRAGMENT;
			}
			// HTTP_URL_STRIP_AUTH becomes HTTP_URL_STRIP_USER and HTTP_URL_STRIP_PASS
			else if ($flags & HTTP_URL_STRIP_AUTH)
			{
				$flags |= HTTP_URL_STRIP_USER;
				$flags |= HTTP_URL_STRIP_PASS;
			}
			
			// Parse the original URL
			$parse_url = is_array($url) ? $url : parse_url($url);
			
			// Scheme and Host are always replaced
			if (isset($parts['scheme']))
				$parse_url['scheme'] = $parts['scheme'];
			if (isset($parts['host']))
				$parse_url['host'] = $parts['host'];
			
			// (If applicable) Replace the original URL with it's new parts
			if ($flags & HTTP_URL_REPLACE)
			{
				foreach ($keys as $key)
				{
					if (isset($parts[$key]))
						$parse_url[$key] = $parts[$key];
				}
			}
			else
			{
				// Join the original URL path with the new path
				if (isset($parts['path']) && ($flags & HTTP_URL_JOIN_PATH))
				{
					if (isset($parse_url['path']))
						$parse_url['path'] = rtrim(str_replace(basename($parse_url['path']), '', $parse_url['path']), '/') . '/' . ltrim($parts['path'], '/');
					else
						$parse_url['path'] = $parts['path'];
				}
				
				// Join the original query string with the new query string
				if (isset($parts['query']) && ($flags & HTTP_URL_JOIN_QUERY))
				{
					if (isset($parse_url['query']))
						$parse_url['query'] .= '&' . $parts['query'];
					else
						$parse_url['query'] = $parts['query'];
				}
			}
				
			// Strips all the applicable sections of the URL
			// Note: Scheme and Host are never stripped
			foreach ($keys as $key)
			{
				if ($flags & (int)constant('HTTP_URL_STRIP_' . strtoupper($key)))
					unset($parse_url[$key]);
			}
			
			
			$new_url = $parse_url;
			
			return 
				 ((isset($parse_url['scheme'])) ? $parse_url['scheme'] . '://' : '')
				.((isset($parse_url['user'])) ? $parse_url['user'] . ((isset($parse_url['pass'])) ? ':' . $parse_url['pass'] : '') .'@' : '')
				.((isset($parse_url['host'])) ? $parse_url['host'] : '')
				.((isset($parse_url['port'])) ? ':' . $parse_url['port'] : '')
				.((isset($parse_url['path'])) ? $parse_url['path'] : '')
				.((isset($parse_url['query']) && $parse_url['query'] != '') ? '?' . $parse_url['query'] : '')
				.((isset($parse_url['fragment'])) ? '#' . $parse_url['fragment'] : '')
			;
		}
	}

// Display a number of lines of a file around a certain point, highlighted
function display_file_at_line($what = 'Information', $file, $hline, $around = 7) {
	echo '<style>.db-lnum {border-right: 1px solid #888;color: #000; } body{font-family: Tahoma, Verdana;}</style><p>'.$what.' on <strong>Line ' . $hline . '</strong> of <code>'.$file.'</code></p><pre>';
	
	if($file == '') echo '&laquo;not a file&raquo;';
	else {
		$hilite = highlight_file($file, true);
		$arrLines = explode('<br />', $hilite);
		$lines = count($arrLines) - 1;
		
		for($i = -$around; $i < $around + 1; $i++) {
			$line = $hline + $i - 1;
			if($line < 1 || $line > $lines)
				continue;
			$st = ($i == 0 ? '<b style="background:#ff8;">' : '');
			$en = ($i == 0 ? '</b>' : '');
			echo '<span class="db-lnum">' . str_pad($line + 1, 5, " ", STR_PAD_LEFT) . ' </span>'  . $st . str_replace("\n", '', str_replace("\t", '    ', $arrLines[$line])) . "\n" . $en;
		}
	}	
	echo '</pre>';
	
	return strip_tags($arrLines[$hline - 1]);
}
	
	
// Include pretty dBug

/*********************************************************************************************************************\
 * LAST UPDATE
 * ============
 * March 22, 2007
 *
 *
 * AUTHOR
 * =============
 * Kwaku Otchere 
 * ospinto@hotmail.com
 * 
 * Thanks to Andrew Hewitt (rudebwoy@hotmail.com) for the idea and suggestion
 * 
 * All the credit goes to ColdFusion's brilliant cfdump tag
 * Hope the next version of PHP can implement this or have something similar
 * I love PHP, but var_dump BLOWS!!!
 *
 * FOR DOCUMENTATION AND MORE EXAMPLES: VISIT http://dbug.ospinto.com
 *
 *
 * PURPOSE
 * =============
 * Dumps/Displays the contents of a variable in a colored tabular format
 * Based on the idea, javascript and css code of Macromedia's ColdFusion cfdump tag
 * A much better presentation of a variable's contents than PHP's var_dump and print_r functions
 *
 *
 * USAGE
 * =============
 * new dBug ( variable [,forceType] );
 * example:
 * new dBug ( $myVariable );
 *
 * 
 * if the optional "forceType" string is given, the variable supplied to the 
 * function is forced to have that forceType type. 
 * example: new dBug( $myVariable , "array" );
 * will force $myVariable to be treated and dumped as an array type, 
 * even though it might originally have been a string type, etc.
 *
 * NOTE!
 * ==============
 * forceType is REQUIRED for dumping an xml string or xml file
 * new dBug ( $strXml, "xml" );
 * 
\*********************************************************************************************************************/

class dBug {
	
	var $xmlDepth=array();
	var $xmlCData;
	var $xmlSData;
	var $xmlDData;
	var $xmlCount=0;
	var $xmlAttrib;
	var $xmlName;
	var $arrType=array("array","object","resource","boolean","NULL");
	var $bInitialized = false;
	var $bCollapsed = false;
	var $arrHistory = array();
	
	//constructor
	function dBug($var,$forceType="",$bCollapsed=false) {
		//include js and css scripts
		if(!defined('BDBUGINIT')) {
			define("BDBUGINIT", TRUE);
			$this->initJSandCSS();
		}
		$arrAccept=array("array","object","xml"); //array of variable types that can be "forced"
		$this->bCollapsed = $bCollapsed;
		if(in_array($forceType,$arrAccept))
			$this->{"varIs".ucfirst($forceType)}($var);
		else
			$this->checkType($var);
	}

	function getVariableName() {
		if(defined('BDBUGVAR'))
			return BDBUGVAR;
			
		$arrBacktrace = debug_backtrace();
		
		$call = 0;
		while(is_array($arrBacktrace[$call]) && $arrBacktrace[$call]['function'] != 'v' && $arrBacktrace[$call]['function'] != 's')
			$call++;
			
		$info = $arrBacktrace[$call];
		
		$line_str = display_file_at_line('Var Dump', $info['file'], $info['line']);
		
		preg_match('/\b[vs]\s*\(\s*(.+?)\s*\);/i', $line_str, $arrMatches);
		
		define('BDBUGVAR', $arrMatches[1]);
		
		return BDBUGVAR;
	}

	//get variable name (not used - Nate Ferrero)
	function xxgetVariableNamexx() {
		$arrBacktrace = debug_backtrace();

		//possible 'included' functions
		$arrInclude = array("include","include_once","require","require_once");
	
		//check for any included/required files. if found, get array of the last included file (they contain the right line numbers)
		for($i=count($arrBacktrace)-1; $i>=0; $i--) {
			$arrCurrent = $arrBacktrace[$i];
			if(array_key_exists("function", $arrCurrent) && 
				(in_array($arrCurrent["function"], $arrInclude) || (0 != strcasecmp($arrCurrent["function"], "dbug"))))
				continue;

			$arrFile = $arrCurrent;
			
			break;
		}
		
		if(isset($arrFile)) {
			$arrLines = file($arrFile["file"]);
			$code = $arrLines[($arrFile["line"]-1)];
	
			//find call to dBug class
			preg_match('/\bnew dBug\s*\(\s*(.+)\s*\);/i', $code, $arrMatches);
			
			return $arrMatches[1];
		}
		return "";
	}
	
	//create the main table header
	function makeTableHeader($type,$header,$colspan=2) {
		if(!$this->bInitialized) {
			$header = $this->getVariableName() . " (" . $header . ")";
			$this->bInitialized = true;
		}
		$str_i = ($this->bCollapsed) ? "style=\"font-style:italic\" " : ""; 
		
		echo "<table cellspacing=2 cellpadding=3 class=\"dBug_".$type."\">
				<tr>
					<td ".$str_i."class=\"dBug_".$type."Header\" colspan=".$colspan." onClick='dBug_toggleTable(this)'>".$header."</td>
				</tr>";
	}
	
	//create the table row header
	function makeTDHeader($type,$header) {
		$str_d = ($this->bCollapsed) ? " style=\"display:none\"" : "";
		echo "<tr".$str_d.">
				<td valign=\"top\" onClick='dBug_toggleRow(this)' class=\"dBug_".$type."Key\">".$header."</td>
				<td>";
	}
	
	//close table row
	function closeTDRow() {
		return "</td></tr>\n";
	}
	
	//error
	function  error($type) {
		$error="Error: Variable cannot be a";
		// this just checks if the type starts with a vowel or "x" and displays either "a" or "an"
		if(in_array(substr($type,0,1),array("a","e","i","o","u","x")))
			$error.="n";
		return ($error." ".$type." type");
	}

	//check variable type
	function checkType($var) {
		
		if(method_exists($var, 'get_array'))
			$var = $var->get_array();
		
		switch(gettype($var)) {
			case "resource":
				$this->varIsResource($var);
				break;
			case "model":
				break;
			case "object":
				if(is_subclass_of($var, 'App_Module'))
					$this->varIsModule($var);
				else
					$this->varIsObject($var);
				break;
			case "array":
				$this->varIsArray($var);
				break;
			case "NULL":
				$this->varIsNULL();
				break;
			case "boolean":
				$this->varIsBoolean($var);
				break;
			default:
				$var=($var=="") ? '""' : $var;
				echo "<pre>$var</pre>";
				break;
		}
	}
	
	//if variable is a NULL type
	function varIsNULL() {
		echo "NULL";
	}
	
	//if variable is a boolean type
	function varIsBoolean($var) {
		$var=($var==1) ? "TRUE" : "FALSE";
		echo $var;
	}
			
	//if variable is an array type
	function varIsArray($var) {
		$var_ser = serialize($var);
		array_push($this->arrHistory, $var_ser);
		
		$this->makeTableHeader("array","array");
		if(is_array($var)) {
			foreach($var as $key=>$value) {
				$this->makeTDHeader("array",$key);
				
				//check for recursion
				if(is_array($value)) {
					$var_ser = serialize($value);
					if(in_array($var_ser, $this->arrHistory, TRUE))
						$value = "*RECURSION*";
				}
				
				if(in_array(gettype($value),$this->arrType))
					$this->checkType($value);
				else {
					$value=(trim($value)=="") ? "[empty string]" : $value;
					echo $value;
				}
				echo $this->closeTDRow();
			}
		}
		else echo "<tr><td>".$this->error("array").$this->closeTDRow();
		array_pop($this->arrHistory);
		echo "</table>";
	}
	
	//if variable is an object type
	function varIsObject($var) {
		$var_ser = serialize($var);
		array_push($this->arrHistory, $var_ser);
		$this->makeTableHeader("object","object");
		
		if(is_object($var)) {
			$arrObjVars=get_object_vars($var);
			foreach($arrObjVars as $key=>$value) {

				$value=(!is_object($value) && !is_array($value) && trim($value)=="") ? '' : $value;
				$this->makeTDHeader("object",$key);
				
				//check for recursion
				if(is_object($value)||is_array($value)) {
					$var_ser = serialize($value);
					if(in_array($var_ser, $this->arrHistory, TRUE)) {
						$value = (is_object($value)) ? "*RECURSION* -> $".get_class($value) : "*RECURSION*";

					}
				}
				if(in_array(gettype($value),$this->arrType))
					$this->checkType($value);
				else echo $value;
				echo $this->closeTDRow();
			}
			/*$arrObjMethods=get_class_methods(get_class($var));
			foreach($arrObjMethods as $key=>$value) {
				$this->makeTDHeader("object",$value);
				echo "[function]".$this->closeTDRow();
			}*/
		}
		else echo "<tr><td>".$this->error("object").$this->closeTDRow();
		array_pop($this->arrHistory);
		echo "</table>";
	}

	//if variable is an model type
	function varIsModule($var) {
		$var_ser = serialize($var);
		array_push($this->arrHistory, $var_ser);
		$this->makeTableHeader("module","Evolution Module: " . $var->_id_map());
		
		if(is_object($var)) {
			$arrObjVars=get_object_vars($var);
			foreach($arrObjVars as $key=>$value) {

				$value=(!is_object($value) && !is_array($value) && $value=="") ? '' : $value;
				$this->makeTDHeader("module",$key);
				
				//check for recursion
				if(is_object($value)||is_array($value)) {
					$var_ser = serialize($value);
					if(in_array($var_ser, $this->arrHistory, TRUE)) {
						$value = (is_object($value)) ? "*RECURSION* -> $".get_class($value) : "*RECURSION*";

					}
				}
				if(in_array(gettype($value),$this->arrType))
					$this->checkType($value);
				else echo $value;
				echo $this->closeTDRow();
			}
		}
		else echo "<tr><td>".$this->error("object").$this->closeTDRow();
		array_pop($this->arrHistory);
		echo "</table>";
	}

	//if variable is a resource type
	function varIsResource($var) {
		$this->makeTableHeader("resourceC","resource",1);
		echo "<tr>\n<td>\n";
		switch(get_resource_type($var)) {
			case "fbsql result":
			case "mssql result":
			case "msql query":
			case "pgsql result":
			case "sybase-db result":
			case "sybase-ct result":
			case "mysql result":
				$db=current(explode(" ",get_resource_type($var)));
				$this->varIsDBResource($var,$db);
				break;
			case "gd":
				$this->varIsGDResource($var);
				break;
			case "xml":
				$this->varIsXmlResource($var);
				break;
			default:
				echo get_resource_type($var).$this->closeTDRow();
				break;
		}
		echo $this->closeTDRow()."</table>\n";
	}

	//if variable is a database resource type
	function varIsDBResource($var,$db="mysql") {
		if($db == "pgsql")
			$db = "pg";
		if($db == "sybase-db" || $db == "sybase-ct")
			$db = "sybase";
		$arrFields = array("name","type","flags");	
		$numrows=call_user_func($db."_num_rows",$var);
		$numfields=call_user_func($db."_num_fields",$var);
		$this->makeTableHeader("resource",$db." result",$numfields+1);
		echo "<tr><td class=\"dBug_resourceKey\">&nbsp;</td>";
		for($i=0;$i<$numfields;$i++) {
			$field_header = "";
			for($j=0; $j<count($arrFields); $j++) {
				$db_func = $db."_field_".$arrFields[$j];
				if(function_exists($db_func)) {
					$fheader = call_user_func($db_func, $var, $i). " ";
					if($j==0)
						$field_name = $fheader;
					else
						$field_header .= $fheader;
				}
			}
			$field[$i]=call_user_func($db."_fetch_field",$var,$i);
			echo "<td class=\"dBug_resourceKey\" title=\"".$field_header."\">".$field_name."</td>";
		}
		echo "</tr>";
		for($i=0;$i<$numrows;$i++) {
			$row=call_user_func($db."_fetch_array",$var,constant(strtoupper($db)."_ASSOC"));
			echo "<tr>\n";
			echo "<td class=\"dBug_resourceKey\">".($i+1)."</td>"; 
			for($k=0;$k<$numfields;$k++) {
				$tempField=$field[$k]->name;
				$fieldrow=$row[($field[$k]->name)];
				$fieldrow=($fieldrow=="") ? "[empty string]" : $fieldrow;
				echo "<td>".$fieldrow."</td>\n";
			}
			echo "</tr>\n";
		}
		echo "</table>";
		if($numrows>0)
			call_user_func($db."_data_seek",$var,0);
	}
	
	//if variable is an image/gd resource type
	function varIsGDResource($var) {
		$this->makeTableHeader("resource","gd",2);
		$this->makeTDHeader("resource","Width");
		echo imagesx($var).$this->closeTDRow();
		$this->makeTDHeader("resource","Height");
		echo imagesy($var).$this->closeTDRow();
		$this->makeTDHeader("resource","Colors");
		echo imagecolorstotal($var).$this->closeTDRow();
		echo "</table>";
	}
	
	//if variable is an xml type
	function varIsXml($var) {
		$this->varIsXmlResource($var);
	}
	
	//if variable is an xml resource type
	function varIsXmlResource($var) {
		$xml_parser=xml_parser_create();
		xml_parser_set_option($xml_parser,XML_OPTION_CASE_FOLDING,0); 
		xml_set_element_handler($xml_parser,array(&$this,"xmlStartElement"),array(&$this,"xmlEndElement")); 
		xml_set_character_data_handler($xml_parser,array(&$this,"xmlCharacterData"));
		xml_set_default_handler($xml_parser,array(&$this,"xmlDefaultHandler")); 
		
		$this->makeTableHeader("xml","xml document",2);
		$this->makeTDHeader("xml","xmlRoot");
		
		//attempt to open xml file
		$bFile=(!($fp=@fopen($var,"r"))) ? false : true;
		
		//read xml file
		if($bFile) {
			while($data=str_replace("\n","",fread($fp,4096)))
				$this->xmlParse($xml_parser,$data,feof($fp));
		}
		//if xml is not a file, attempt to read it as a string
		else {
			if(!is_string($var)) {
				echo $this->error("xml").$this->closeTDRow()."</table>\n";
				return;
			}
			$data=$var;
			$this->xmlParse($xml_parser,$data,1);
		}
		
		echo $this->closeTDRow()."</table>\n";
		
	}
	
	//parse xml
	function xmlParse($xml_parser,$data,$bFinal) {
		if (!xml_parse($xml_parser,$data,$bFinal)) { 
				   die(sprintf("XML error: %s at line %d\n", 
							   xml_error_string(xml_get_error_code($xml_parser)), 
							   xml_get_current_line_number($xml_parser)));
		}
	}
	
	//xml: inititiated when a start tag is encountered
	function xmlStartElement($parser,$name,$attribs) {
		$this->xmlAttrib[$this->xmlCount]=$attribs;
		$this->xmlName[$this->xmlCount]=$name;
		$this->xmlSData[$this->xmlCount]='$this->makeTableHeader("xml","xml element",2);';
		$this->xmlSData[$this->xmlCount].='$this->makeTDHeader("xml","xmlName");';
		$this->xmlSData[$this->xmlCount].='echo "<strong>'.$this->xmlName[$this->xmlCount].'</strong>".$this->closeTDRow();';
		$this->xmlSData[$this->xmlCount].='$this->makeTDHeader("xml","xmlAttributes");';
		if(count($attribs)>0)
			$this->xmlSData[$this->xmlCount].='$this->varIsArray($this->xmlAttrib['.$this->xmlCount.']);';
		else
			$this->xmlSData[$this->xmlCount].='echo "&nbsp;";';
		$this->xmlSData[$this->xmlCount].='echo $this->closeTDRow();';
		$this->xmlCount++;
	} 
	
	//xml: initiated when an end tag is encountered
	function xmlEndElement($parser,$name) {
		for($i=0;$i<$this->xmlCount;$i++) {
			eval($this->xmlSData[$i]);
			$this->makeTDHeader("xml","xmlText");
			echo (!empty($this->xmlCData[$i])) ? $this->xmlCData[$i] : "&nbsp;";
			echo $this->closeTDRow();
			$this->makeTDHeader("xml","xmlComment");
			echo (!empty($this->xmlDData[$i])) ? $this->xmlDData[$i] : "&nbsp;";
			echo $this->closeTDRow();
			$this->makeTDHeader("xml","xmlChildren");
			unset($this->xmlCData[$i],$this->xmlDData[$i]);
		}
		echo $this->closeTDRow();
		echo "</table>";
		$this->xmlCount=0;
	} 
	
	//xml: initiated when text between tags is encountered
	function xmlCharacterData($parser,$data) {
		$count=$this->xmlCount-1;
		if(!empty($this->xmlCData[$count]))
			$this->xmlCData[$count].=$data;
		else
			$this->xmlCData[$count]=$data;
	} 
	
	//xml: initiated when a comment or other miscellaneous texts is encountered
	function xmlDefaultHandler($parser,$data) {
		//strip '<!--' and '-->' off comments
		$data=str_replace(array("&lt;!--","--&gt;"),"",htmlspecialchars($data));
		$count=$this->xmlCount-1;
		if(!empty($this->xmlDData[$count]))
			$this->xmlDData[$count].=$data;
		else
			$this->xmlDData[$count]=$data;
	}

	function initJSandCSS() {
		echo <<<SCRIPTS
			<script language="JavaScript">
			/* code modified from ColdFusion's cfdump code */
				function dBug_toggleRow(source) {
					var target = (document.all) ? source.parentElement.cells[1] : source.parentNode.lastChild;
					dBug_toggleTarget(target,dBug_toggleSource(source));
				}
				
				function dBug_toggleSource(source) {
					if (source.style.fontStyle=='italic') {
						source.style.fontStyle='normal';
						source.title='click to collapse';
						return 'open';
					} else {
						source.style.fontStyle='italic';
						source.title='click to expand';
						return 'closed';
					}
				}
			
				function dBug_toggleTarget(target,switchToState) {
					target.style.display = (switchToState=='open') ? '' : 'none';
				}
			
				function dBug_toggleTable(source) {
					var switchToState=dBug_toggleSource(source);
					if(document.all) {
						var table=source.parentElement.parentElement;
						for(var i=1;i<table.rows.length;i++) {
							target=table.rows[i];
							dBug_toggleTarget(target,switchToState);
						}
					}
					else {
						var table=source.parentNode.parentNode;
						for (var i=1;i<table.childNodes.length;i++) {
							target=table.childNodes[i];
							if(target.style) {
								dBug_toggleTarget(target,switchToState);
							}
						}
					}
				}
			</script>
			
			<style type="text/css">
				table.dBug_array,table.dBug_object,table.dBug_module,table.dBug_resource,table.dBug_resourceC,table.dBug_xml {
					font-family:Verdana, Arial, Helvetica, sans-serif; color:#000000; font-size:12px;
				}
				
				.dBug_arrayHeader,
				.dBug_objectHeader,
				.dBug_moduleHeader,
				.dBug_resourceHeader,
				.dBug_resourceCHeader,
				.dBug_xmlHeader 
					{ font-weight:bold; color:#FFFFFF; cursor:pointer; }
				
				.dBug_arrayKey,
				.dBug_objectKey,
				.dBug_moduleKey,
				.dBug_xmlKey 
					{ cursor:pointer; }
					
				/* array */
				table.dBug_array { background-color:#006600; }
				table.dBug_array td { background-color:#FFFFFF; }
				table.dBug_array td.dBug_arrayHeader { background-color:#009900; }
				table.dBug_array td.dBug_arrayKey { background-color:#CCFFCC; }
				
				/* object */
				table.dBug_object { background-color:#0000CC; }
				table.dBug_object td { background-color:#FFFFFF; }
				table.dBug_object td.dBug_objectHeader { background-color:#4444CC; }
				table.dBug_object td.dBug_objectKey { background-color:#CCDDFF; }
				
				/* object */
				table.dBug_module { background-color:#CCAA00; }
				table.dBug_module td { background-color:#FFFFFF; }
				table.dBug_module td.dBug_moduleHeader { background-color:#EECC00; }
				table.dBug_module td.dBug_moduleKey { background-color:#FFEECC; }
				
				/* resource */
				table.dBug_resourceC { background-color:#884488; }
				table.dBug_resourceC td { background-color:#FFFFFF; }
				table.dBug_resourceC td.dBug_resourceCHeader { background-color:#AA66AA; }
				table.dBug_resourceC td.dBug_resourceCKey { background-color:#FFDDFF; }
				
				/* resource */
				table.dBug_resource { background-color:#884488; }
				table.dBug_resource td { background-color:#FFFFFF; }
				table.dBug_resource td.dBug_resourceHeader { background-color:#AA66AA; }
				table.dBug_resource td.dBug_resourceKey { background-color:#FFDDFF; }
				
				/* xml */
				table.dBug_xml { background-color:#888888; }
				table.dBug_xml td { background-color:#FFFFFF; }
				table.dBug_xml td.dBug_xmlHeader { background-color:#AAAAAA; }
				table.dBug_xml td.dBug_xmlKey { background-color:#DDDDDD; }
			</style>
SCRIPTS;
	}

}

// Function turns array(name => [0 = a, 1 = b]) to array(0 => [name = a], 1 => [name = b]);
function invert_array($arr) {
	$new_arr = array();
	foreach($arr as $field => $sub_arr) {
		foreach($sub_arr as $index => $value) {
			if(!isset($new_arr[$index]))
				$new_arr[$index] = array();
			$new_arr[$index][$field] = $value;
		}
	}
	return $new_arr;
}

function v(&$obj) {
	new dBug($obj);
	die;
}

function s($obj) {
	$x = new dBug($obj);
	echo '<hr/>';
	$x->getVariableName();
	die;
}

function invert_sub_arrays($arr) {
	$new_arr = array();
	foreach($arr as $key => $sub_arr) {
		$new_arr[$key] = invert_array($sub_arr);
	}
	return $new_arr;
} 

function logg($var) {
	file_put_contents(ROOT . '-logg.txt', '' . print_r($var, true));
}

# Get array element, with default if not present
function element(&$arr, $key, $default = null) {
	return isset($arr[$key]) ? $arr[$key] : $default;
}

# deep JSON encoder
function json_encode_safe($obj) {
	return json_encode(prepareDeepEncode($obj));
}
function prepareDeepEncode($obj) {
	if(is_object($obj)) {
		if(method_exists($obj, '__toArray'))
			return prepareDeepEncode($obj->__toArray());
		if(isset($obj->model) && method_exists($obj->model, 'get_array'))
			return prepareDeepEncode($obj->model->get_array());
		else
			return array('type' => 'object', 'encoding' => 'base64', 'serialized' => base64_encode(serialize($obj)));
	} else if(is_array($obj)) {
		$o = array();
		foreach($obj as $key => $value) {
			if($value !== '@MYSQL_MODEL_SECONDARY_REQUEST@' && !is_null($value) && $value !== '')
				$o[$key] = prepareDeepEncode($value);
		}
		return $o;
	} else if(is_string($obj)) {
		return utf8_encode($obj);
	} else if(is_numeric($obj) || is_bool($obj) || is_null($obj)) {
		return $obj;
	} else {
		return array('type' => 'unknown', 'encoding' => 'base64', 'serialized' => base64_encode(serialize($obj)));
	}
}

// Handles single/multi uploads
function get_uploaded_files($name) {
	if(is_array($_FILES[$name][0]['name']))
		return invert_array($_FILES['photo'][0]);
	else
		return $_FILES[$name];
}

function engine_get_configuration($library) {
	//die(var_dump($library));
	//die(var_dump(file_exists(SUPER_ROOT_CONFIGURE."/$library".'.yaml')));
	if(file_exists(SUPER_ROOT_CONFIGURE."/$library".'.yaml'))
		return e::helper('yaml')->file(SUPER_ROOT_CONFIGURE."/$library".'.yaml');
	else
		return e::helper('yaml')->file(ROOT_ENGINE.'/configure/'.$library.'.yaml');
}

function engine_get_configuration_time($library) {
	if(file_exists(SUPER_ROOT_CONFIGURE."/$library".'.yaml'))
		return @filemtime(SUPER_ROOT_CONFIGURE.'/'.$library.'.yaml');
	else
		return @filemtime(ROOT_ENGINE.'/configure/'.$library.'.yaml');
}

function draw_paginate($page=1,$row_cnt=1,$page_count=PAGINATE_COUNT,$get_var='page',$size='default',$type="normal"){
	$t_pages=ceil($row_cnt/$page_count);
	$page_start=(($page-1)*$page_count)+1;
	$page_end1=$page*$page_count;
	$page_end=$page_end1<$row_cnt?$page_end1:$row_cnt;
	$retval='<div class="pagination-left pagination-'.$size.'">';
	if($row_cnt==0){
		$retval.="<p>No results</p>"; 
	}else{
		$retval.="<p>".($size == "mini" ? "" : "Showing ")."<b>$page_start-$page_end</b> of <b>$row_cnt</b> results</p>"; 
	}
	if($type=='normal'){
		if($size=='mini')
			$caps=array('first'=>'<p class="pagination">','last'=>'</p>');
		else
			$caps=array('first'=>'<p class="pagination ta-right">','last'=>'</p>');	
	}
	if($type=='minimal')$caps=array('first'=>'<ul class="pagination">','last'=>'</ul>');
	
	$retval.='</div><div class="pagination-right pagination-'.$size.'">';

	$source = 'all';
	e::filter($source, 'make_param_link', array($get_var, '-'.$get_var, true)); // Remove the page variable so it doesn't get duplicated
	$get_var = strlen($source) > 0 ? $source . '&' . $get_var : $get_var;
	
	if($size == 'mini' && $t_pages>1) {
		// Make the smallest paging possible, good for sidebars
		$retval.= $caps['first'];
		if($page!=1)
			$retval.="<a href='?$get_var=1'>&laquo;</a>";
		
		if($page == 4)
			$retval.="<a href='?$get_var=1'>1</a><a href='?$get_var=2'>2</a>";
		else if($page >= 3)
			$retval.="<a href='?$get_var=1'>1</a>" . ($page > 4 ? "..." : "");
		
		for($a = -1 + $page;$a <= 1 + $page; $a++) {
			if($a==$page) {
				$retval.="<span class='current_page'>$a</span>";
			} else if($a > 0 && $a <= $t_pages) {
				$retval.="<a href='?$get_var=$a'>$a</a>";
			}
		}
		
		if($page < $t_pages - 3)
			$retval.="...<a href='?$get_var=$t_pages'>$t_pages</a>";
		
		if($page == $t_pages - 2)
			$retval.="<a href='?$get_var=$t_pages'>$t_pages</a>";
		if($page == $t_pages - 3)
			$retval.="<a href='?$get_var=".($t_pages-1)."'>".($t_pages-1)."</a><a href='?$get_var=$t_pages'>$t_pages</a>";
		
		if($page!=$t_pages){
			$retval.="<a href='?$get_var=$t_pages'>&raquo;</a>";
		}
		
		$retval.="</p>";
	} else if($t_pages>1){
		$retval.= $caps['first'];
		if($page!=1){
			$retval.=draw_link($type,"?$get_var=1","&laquo;");
		}
		
		if($t_pages>7){
			if($page<3 || $page>($t_pages-2)){
				for($a=1;$a<=3;$a++){
					if($a==$page){
						$retval.=draw_link($type,'',$a,true);
					}else{
						$retval.=draw_link($type,"?$get_var=$a",$a);
					}
				}
				$retval.="...";
			}else{
				for($a=1;$a<=2;$a++){
					if($a==$page){
						$retval.=draw_link($type,'',$a,true);
					}else{
						$retval.=draw_link($type,"?$get_var=$a",$a);
					}
				}
			}
			
			if($page==3){
				
				for($a=$page;$a<=($page+2);$a++){
					if($a==$page){
						$retval.=draw_link($type,'',$a,true);
					}else{
						$retval.=draw_link($type,"?$get_var=$a",$a);
					}
				}
				$retval.="...";
			}
			if($page>3 && $page<($t_pages-2)){
				$retval.="...";
				for($a=($page-1);$a<=($page+1);$a++){
					if($a==$page){
						$retval.=draw_link($type,'',$a,true);
					}else{
						$retval.=draw_link($type,"?$get_var=$a",$a);
					}
				}
				$retval.="...";
			}

			if($page==($t_pages-2)){
				$retval.="...";
				for($a=($t_pages-4);$a<=($t_pages-3);$a++){
					if($a==$page){
						$retval.=draw_link($type,'',$a,true);
					}else{
						$retval.=draw_link($type,"?$get_var=$a",$a);
					}
				}
				
			}
			
			if($page<3 || $page>=($t_pages-2)){
				for($a=($t_pages-2);$a<=$t_pages;$a++){
					if($a==$page){
						$retval.=draw_link($type,'',$a,true);
					}else{
						$retval.=draw_link($type,"?$get_var=$a",$a);
					}
				}
			}else{
				for($a=($t_pages-1);$a<=$t_pages;$a++){
					if($a==$page){
						$retval.=draw_link($type,'',$a,true);
					}else{
						$retval.=draw_link($type,"?$get_var=$a",$a);
					}
				}
				
			}
		}else{
			for($a=1;$a<=$t_pages;$a++){
				if($a==$page){
					$retval.=draw_link($type,'',$a,true);
				}else{
					$retval.=draw_link($type,"?$get_var=$a",$a);
				}
			}
		}
		if($page!=$t_pages){
			$retval.=draw_link($type,"?$get_var=$t_pages",'&raquo;');
		}
		
		$retval.=$caps['last'];
	}
	$retval.='</div>';
	return $retval;	
}

function draw_link($type='minimal',$href='',$content='',$current=false){
	switch($type){
		case 'minimal':
			if($current){
				return "<li class='sel'><span class='divider'/><a href='$href'>$content</a></li>" ;
			}else{
				return "<li class='sel'><span class='divider'/><a href='$href'>$content</a></li>";
			}
			break;
	
		case 'normal':
			if($current){
				return "<span class='current_page'>$content</span>" ;
			}else{
				return "<a href='$href'>$content</a>";
			}
			break;
	}	
}

function output_file($file, $name, $mime_type='')
{
 /*
 This function takes a path to a file to output ($file), 
 the filename that the browser will see ($name) and 
 the MIME type of the file ($mime_type, optional).
 
 If you want to do something on download abort/finish,
 register_shutdown_function('function_name');
 */
 if(!is_readable($file)) return false;
 
 $size = filesize($file);
 $name = rawurldecode($name);
 
 /* Figure out the MIME type (if not specified) */
 $known_mime_types=array(
 	"pdf" => "application/pdf",
 	"txt" => "text/plain",
 	"csv" => "text/plain",
 	"html" => "text/html",
 	"htm" => "text/html",
	"exe" => "application/octet-stream",
	"zip" => "application/zip",
	"doc" => "application/msword",
	"xls" => "application/vnd.ms-excel",
	"ppt" => "application/vnd.ms-powerpoint",
	"gif" => "image/gif",
	"png" => "image/png",
	"jpeg"=> "image/jpg",
	"jpg" =>  "image/jpg",
	"php" => "text/plain"
 );
 
 if($mime_type==''){
	 $file_extension = strtolower(substr(strrchr($file,"."),1));
	 if(array_key_exists($file_extension, $known_mime_types)){
		$mime_type=$known_mime_types[$file_extension];
	 } else {
		$mime_type="application/force-download";
	 };
 };
 
 @ob_end_clean(); //turn off output buffering to decrease cpu usage
 
 // required for IE, otherwise Content-Disposition may be ignored
 if(ini_get('zlib.output_compression'))
  ini_set('zlib.output_compression', 'Off');
 
 header('Content-Type: ' . $mime_type);
 header('Content-Disposition: attachment; filename="'.$name.'"');
 header("Content-Transfer-Encoding: binary");
 header('Accept-Ranges: bytes');
 
 /* The three lines below basically make the 
    download non-cacheable */
 header("Cache-control: private");
 header('Pragma: private');
 header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
 
 // multipart-download and download resuming support
 if(isset($_SERVER['HTTP_RANGE']))
 {
	list($a, $range) = explode("=",$_SERVER['HTTP_RANGE'],2);
	list($range) = explode(",",$range,2);
	list($range, $range_end) = explode("-", $range);
	$range=intval($range);
	if(!$range_end) {
		$range_end=$size-1;
	} else {
		$range_end=intval($range_end);
	}
 
	$new_length = $range_end-$range+1;
	header("HTTP/1.1 206 Partial Content");
	header("Content-Length: $new_length");
	header("Content-Range: bytes $range-$range_end/$size");
 } else {
	$new_length=$size;
	header("Content-Length: ".$size);
 }
 
 /* output the file itself */
 $chunksize = 1*(1024*1024); //you may want to change this
 $bytes_send = 0;
 if ($file = fopen($file, 'r'))
 {
	if(isset($_SERVER['HTTP_RANGE']))
	fseek($file, $range);
 
	while(!feof($file) && 
		(!connection_aborted()) && 
		($bytes_send<$new_length)
	      )
	{
		$buffer = fread($file, $chunksize);
		print($buffer); //echo($buffer); // is also possible
		flush();
		$bytes_send += strlen($buffer);
	}
 fclose($file);
 } else return false;
 
die();
}