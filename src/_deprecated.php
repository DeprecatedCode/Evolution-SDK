<?php
/*
   Copyright 2010 Verschoyle Innovation Corp.

   Licensed under the Apache License, Version 2.0 (the "License");
   you may not use this file except in compliance with the License.
   You may obtain a copy of the License at

     http://www.apache.org/licenses/LICENSE-2.0

   Unless required by applicable law or agreed to in writing, software
   distributed under the License is distributed on an "AS IS" BASIS,
   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
   See the License for the specific language governing permissions and
   limitations under the License.
*/

# include all assets
include_once(ROOT_FRAMEWORK.'/assets/cache.php');
include_all(ROOT_FRAMEWORK.'/assets');
include_all(ROOT_FRAMEWORK.'/extend');
include_all(ROOT_LIBRARY.'/filters');
/**
 * Mini Framework Wrapper
 *
 * @package mini
 * @author David D. Boskovic
 **/
class e_v1
{
	/**
	 * Store loaded objects in this array
	 *
	 * @var array
	 **/
	public static $_deprecated_cache = array();
	   
	/**
	 * Session Instance
	 *
	 * @author David Boskovic
	 */
	// public static $session;
	
	/**
	 * Array of database engines.
	 *
	 * @author David D. Boskovic
	 */
	public static $database_engines;
	
	/**
	 * Access the functionality of a helper.
	 *
	 * @param string $name
	 * @return Helper_xxxxxxxxxxx
	 * @author David D. Boskovic
	 **/
	public static function helper($name, $val = NULL, $val2 = NULL, $val3 = NULL, $val4 = NULL) {
		
		# if the helper has been cached, load up the cached object.
		if(isset(self::$_deprecated_cache['helper'][$name]))
			return self::$_deprecated_cache['helper'][$name];
		
		# if the file hasn't been included yet (faster than include_once)
		if(!isset(self::$_deprecated_cache['included_helpers'][$name])) {
			if(file_exists(ROOT.'/library/helpers/'.$name.'.php'))
				include ROOT.'/library/helpers/'.$name.'.php';
			elseif(file_exists(ROOT.'/library/helpers/'.$name.'/_'.$name.'.php'))
				include ROOT.'/library/helpers/'.$name.'/_'.$name.'.php';
			else
				self::fault(100, 'helper_not_exist', array('helper_name' => $name));
			
			self::$_deprecated_cache['included_helpers'][$name] = true;
		}
		
		# make sure the class exists.
		$class_name = 'Helper_'.$name;
		if(class_exists($class_name))
			return new $class_name($val, $val2, $val3, $val4);
		else return false;
	}
	
	public static function start_session() {
		// self::$session = new Session;
	}
	
	/**
	 * Implement a hook.
	 *
	 * @param string $hook_name 
	 * @param string $obj 
	 * @param string $args 
	 * @return mixed
	 * @author David D. Boskovic
	 */
	public static function hook($hook_name, $obj, $args) {
		if(isset(self::$_deprecated_cache['hooks'][$hook_name])) {
			$func = self::$_deprecated_cache['hooks'][$hook_name];
			return $func($obj, $args);
		}
	}
	
	/**
	 * Load raw data creator.
	 *
	 * @return Utility_Create
	 * @author Nate Ferrero
	 */
	public static function create() {
		if(!isset(self::$_deprecated_cache['create']))
			self::$_deprecated_cache['create'] = new Utility_Create();
		return self::$_deprecated_cache['create'];
	}
	
	/**
	 * Return an instance of an application.
	 *
	 * @param string $appname 
	 * @param string $val 
	 * @param string $val2 
	 * @param string $val3 
	 * @param string $val4 
	 * @return void
	 * @author David D. Boskovic
	 */
	public static function app($appname, $val=null, $val2=null, $val3=null, $val4=null) {
		
		# throw a fault if the application folder doesn't exist.
		if(!file_exists(ROOT.'/applications/'.$appname.'/_application.php')) {
			return self::fault(100, 'application_not_exist', array('application_name' => $appname));
		}
		include_once(ROOT.'/applications/'.$appname.'/_application.php');
		$hn = 'App_'.$appname;
		
		# throw a fault if the application class doesn't exist
		if(!class_exists($hn)) {
			return self::fault(100, 'application_not_exist', array('application_name' => $appname));
		}
		
		if(class_exists($hn)) {
			return new $hn($val, $val2, $val3, $val4);
		}
		else return false;		
	}
	
	/**
	 * Return an instance of an application module.
	 *
	 * @param string $map 
	 * @param int $id 
	 * @return void
	 * @author Nate S. Ferrero
	 */
	public static function map($map, $id = null) {
		$s = explode('.', $map);
		$appname = $s[0];
		$s = explode('(', $s[1]);
		$modname = $s[0];
		$id = count($s) > 1 ? (int) substr($s[1], 0, strlen($s[1]) - 1) : $id;
		if($id == null) return null;
		
		$app = self::app($appname);
		return $app->$modname($id);		
	}
	
	/**
	 * Return an instance of a controller.
	 *
	 * @param string $controller 
	 * @return void
	 * @author David D. Boskovic
	 */
	public static function controller($controller) {
		
		# throw a fault if the application folder doesn't exist.
		if(!file_exists(ROOT.'/controllers/'.$controller.'.php')) {
			self::fault(100, 'controller_not_exist', array('controller' => $controller));
		}
		include_once(ROOT.'/controllers/'.$controller.'.php');
		$hn = 'Controller_'.$controller;
		
		# throw a fault if the application class doesn't exist
		if(!class_exists($hn)) {
			self::fault(100, 'controller_not_exist', array('controller' => $controller));
		}
		
		if(class_exists($hn)) {
			return new $hn;
		}
		else return false;		
	}
	
	
	/**
	 * Return an instance of an upgrade utility.
	 *
	 * @param string $controller 
	 * @return void
	 * @author David D. Boskovic
	 */
	public static function upgrade($upgrade) {
		if($upgrade == '*') {
			$a = array();
			
			$d = dir(ROOT_LIBRARY.'/upgrades/'); 
			while (false!== ($filename = $d->read())) { 
				if($filename == '.' || $filename == '..' || strpos($filename,'.') === 0) continue;
				$a[] = e::upgrade(substr($filename,0,-4));
			} 
			$d->close();
			return $a;
		}

		# throw a fault if the application folder doesn't exist.
		if(!file_exists(ROOT_LIBRARY.'/upgrades/'.$upgrade.'.php')) {
			self::fault(100, 'upgrade_not_exist', array('upgrade' => $upgrade));
		}
		include_once(ROOT_LIBRARY.'/upgrades/'.$upgrade.'.php');
		$hn = 'UpgradeScript_'.$upgrade;
		
		# throw a fault if the application class doesn't exist
		if(!class_exists($hn)) {
			self::fault(100, 'upgrade_not_exist', array('upgrade' => $upgrade));
		}
		
		if(class_exists($hn)) {
			return new $hn;
		}
		else return false;		
	}
	
	/**
	 * Return an instance of an action.
	 *
	 * @param string $controller 
	 * @return void
	 * @author David D. Boskovic
	 */
	private static $actions = array();
	public static function action($action,$val = true) {
		$hn = 'Action_'.$action;
		if(isset(self::$actions[$hn]))
			return self::$actions[$hn];
		# throw a fault if the application folder doesn't exist.
		if(!file_exists(ROOT.'/actions/'.$action.'.php')) {
			self::fault(100, 'action_not_exist', array('action' => $action));
		}
		include_once(ROOT.'/actions/'.$action.'.php');
		
		# throw a fault if the application class doesn't exist
		if(!class_exists($hn)) {
			self::fault(100, 'action_not_exist', array('action' => $action));
		}
		
		if(class_exists($hn)) {
			return (self::$actions[$hn] = new $hn($val));
		}
		else return false;		
	}
	
	
	/**
	 * Access any loaded database engine.
	 *
	 * @param string $dbtype 
	 * @return void
	 * @author David D. Boskovic
	 */
	public static function db($dbtype='mysql') {
		if(!isset(self::$database_engines[$dbtype]))
			{
				return e::$db->mysql;
			}
		else
			return self::$database_engines[$dbtype];
	}

	
	/**
	 * This must be called by the engine.
	 *
	 * @return void
	 * @author David D. Boskovic
	 */
	public static function start_engine($engine) {
		$engine = 'MiniEngine_'.$engine;
		if(class_exists($engine))
			self::$_deprecated_cache['engine'] = new $engine;
		else
			die("The engine could not be started because the engine class could not be found.");
	}
	
	/**
	 * Trigger a specific event key and any attached functionality.
	 *
	 * @param string $name 
	 * @param string $attr 
	 * @return boolean
	 * @author David D. Boskovic
	 */
	public static function event($name, $attr = false) {
		
	}
	
	public static function log($file, $line, $zone, $message) {
		
	}
	
	/**
	 * Check to see if an item has been cached.
	 *
	 * @param string $library 
	 * @param string $key 
	 * @return boolean
	 * @author David D. Boskovic
	 */
	private static function _check_cache($library, $key) {
		return(isset(self::$_deprecated_cache[$library][$key]));
	}
	
	/**
	 * Set the value of a cached item.
	 *
	 * @param string $library 
	 * @param string $key 
	 * @param mixed $value 
	 * @return void
	 * @author David D. Boskovic
	 */
	private static function _set_cache($library, $key, $value) {
		self::$_deprecated_cache[$library][$key] = $value;
	}
	
	/**
	 * Get the cached value.
	 *
	 * @param string $library 
	 * @param string $key 
	 * @return mixed
	 * @author David D. Boskovic
	 */
	private static function _get_cache($library, $key) {
		return self::_check_cache($library, $key) ? self::$_deprecated_cache[$library][$key] : false;
	}
	public static function redirect($url) {
		// Send them on their way
		Header("Location: $url");		
		die('Died in redirect function. Forwarding to: "'.$url.'"');

	}
	
	/**
	 * Generate a system fault.
	 *
	 * @param string $key
	 * @param mixed $custom_error
	 * @return void
	 * @author David D. Boskovic
	 */
	public static function fault($event, $key, $data = array(), $custom_error = false) {
		$nofault = defined('NO_FAULT') ? NO_FAULT : false;
		if($nofault) return '--!fault '.$key;
		
		switch($event) {
			case 'startup':
			case 10:
				$event_text = "Yo, I ran into a problem during startup.";
			break;
			default:
				$event_text = "Screeeeeeech!";
			break;
		}
		$message = file_exists(ROOT_LIBRARY.'/documentation/errors/'.$key.'.md') ? file_get_contents(ROOT_LIBRARY.'/documentation/errors/'.$key.'.md') : "#$key\r\n And we couldn't even find the error we're supposed to be showing.";
		foreach((array) $data as $key => $item) {
			$message = str_replace("{{".$key."}}", $item, $message);
		}
		if(isset($data['application_name']) && file_exists(ROOT_APPLICATIONS.'/'.$data['application_name'].'/documentation/index.md')) {
			$doc = file_get_contents(ROOT_APPLICATIONS.'/'.$data['application_name'].'/documentation/index.md');
		}
		$doc = $doc ? e::helper('markdown')->transform($doc) : false;
		$message = str_replace("@doc", '', $message);
		$message = e::helper('markdown')->transform($message);
		include(ROOT_FRAMEWORK.'/visual/fault.php');
		die;
	}
	
	public static $vars = array();
	public static function variable($name, $val = false) {
		if($val) self::$vars[$name] = $val;
		return isset(self::$vars[$name]) ? self::$vars[$name] : false;
	}
	
	public static function filter(&$source, $filter_name, $vars = array()) {
		$f = '_filter_'.$filter_name;
		if(function_exists($f))
			$f($source, $vars);
		else
			self::fault('filter', 'filter_not_exist', array('name' => $filter_name));
		return $source;
	}

	
} // END class e

function match_type($regex, $var) {
    preg_match($regex, $var, $matches);
    return count($matches) == 0 ? false : true;
}

function include_all($dir) {
	$d = dir($dir); 
	while (false!== ($filename = $d->read())) {
		if (substr($filename, -4) == '.php') {
				$a[] = "/$filename";
		} 
	} 
	$d->close();	
	asort($a);
	foreach($a as $filename) {
		include_once $dir."/$filename";
	}	
}
function parse_dir($dir, $callback, $extra = false) {
	$d = dir($dir); 
	while (false!== ($filename = $d->read())) { 
		if($filename == '.' || $filename == '..') continue;
		$callback($dir, $filename, $extra);
	} 
	$d->close();
}

class Utility_Create {
	public function make() {
		return func_get_args();
	}
}
