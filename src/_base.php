<?php

/**
 * Deprecate These
 *
 * @todo deprecate these includes
 */
include_once('src/functions.php');
include_once(ROOT_LIBRARY.'/filters/default_filters.php');

include_once('_deprecated.php');

/**
 * Evolution Framework Wrapper
 *
 * @package evolution
 * @author David D. Boskovic
 **/
class e extends e_v1
{
	/**
	 * Cache access. Only use this for general cache access. This is the file cache.
	 * use e::$memory for holding specfic object instances.
	 *
	 * @var class e_cache
	 **/
	public static $cache;

	/**
	 * Store objects and stuff in memory for this page load only. Meant to avoid creating the
	 * same object too many times.
	 *
	 * @var array
	 **/
	public static $memory = array();

	/**
	 * Session Instance
	 *
	 * @var class e_session
	 */
	public static $session;

	/**
	 * This is the component loader class. Usage as follows.
	 *
	 * @var class e_component_loader
	 **/
	public static $component;
	public static $com;

	/**
	 * This is the helper loader class. Usage as follows.
	 *
	 * @var class e_helper_loader
	 **/
	public static $helper;

	/**
	 * This is the filter loader class. Usage as follows.
	 *
	 * @var class e_filter_loader
	 **/
	public static $filter;

	/**
	 * This is the url router / manager. See file for usage.
	 *
	 * @var class e_url
	 **/
	public static $url;

	/**
	 * This is the action loader class. Usage as follows.
	 *
	 * @var class e_action_loader
	 **/
	public static $action;
	
	/**
	 * This is the controller loader class. Usage as follows.
	 *
	 * @var class e_controller_loader
	 **/
	public static $controller;

	/**
	 * This is the utility loader class.
	 *
	 * @var class e_utility_loader
	 **/
	public static $utility;

	/**
	 * This is the database engine loader class.
	 *
	 * @var class e_db_loader
	 **/
	public static $db;

	/**
	 * This is the filesystem access class. See file for usage.
	 *
	 * @var class e_filesystem
	 **/
	public static $filesystem;

	/**
	 * This is the event access class. See file for usage.
	 *
	 * @var class e_event
	 **/
	public static $event;

	/**
	 * This is the error reporting class. See file for usage.
	 *
	 * @var class e_event
	 **/
	public static $error;

	/**
	 * This is the configuration access class. See file for usage.
	 *
	 * @var class e_configure
	 **/
	public static $configure;
	
	/**
	 * This is the library YAML access class. See file for usage.
	 *
	 * @var class e_configure
	 **/
	public static $library;

	/**
	 * This is the language file cache.
	 *
	 * @var Array
	 **/
	public static $lang;

	/**
	 * This is the environment data stored in environemnts.yaml
	 *
	 * @var array
	 **/
	public static $env;

	/**
	 * This is the site data
	 *
	 * @var array
	 **/
	public static $site;

	/**
	 * Initialize the framework.
	 *
	 * @return void
	 * @author David Boskovic
	 */
	public static function init() {
		url::initialize();

		self::$utility 		= new e_Utility_Loader;
		self::$configure 	= new e_Configure(ROOT_CONFIGURE);
		self::$library 		= new e_Configure(ROOT_LIBRARY);
		self::$cache 		= new e_Cache;
		self::$event 		= new e_Event;
		self::$env			= self::$configure->get_environment_data();
		self::$helper 		= new e_Helper_Loader(ROOT_HELPERS);
		self::$filter 		= new e_Filter_Loader(ROOT_FILTERS);
		self::$db	 		= new e_Database_Loader;
		self::$component 	= new e_Component_Loader(ROOT_COMPONENTS);
		self::$com 			= self::$component;
		self::$action 		= new e_Action_Loader(ROOT_ACTIONS);
		self::$controller 	= new e_Controller_Loader(ROOT_CONTROLLERS);
		self::$session		= new e_Session;
		self::$filesystem 	= new e_Filesystem;
		self::$error 		= new e_Error;
		self::$url 			= new e_Url;
		self::$lang			= self::$library->get_language_data();
		
		# Prepopulate site info
		$site = array();
		$cache = cache::get('settings', 'general');
		$site["title"]=(string) $cache['site_name'];
		$site["email"]=(string) $cache['support_email'];
		$site["support"]=(string) $cache['support_email'];
		$site["name"]=(string) $cache['site_name'];
		self::$site = (object) $site;
		
		self::$url->init();

	}

	/**
	 * Redirect to another url within the site.
	 *
	 * @param string $url
	 * @param string $use_base
	 * @return void
	 * @author David Boskovic
	 */
	public static function redirect($url, $use_base = true) {
		if(!e::$url->portal) return parent::redirect($url, $use_base = true);

		// add the base url path if this application is installed in a subfolder.
		if($use_base && strpos($url,'/') === 0 || strpos($url, '^') === 0)  {
			if(strpos($url, '^') === 0) {
				$dir = @e::$env['http_path'] ? rtrim(e::$env['http_path'],'/') : '/';
				$portal = e::$url->portal;
				$url = str_replace('^^/',$dir.'/'.$portal.'/', $url);
				$url = str_replace('^/',$dir, $url);
			}
		}

		// Send them on their way
		Header("Location: $url");

		// kill the script so nothing else happens. (warning, all destruct functions will still run)
		die('Died in redirect function. Forwarding to: "'.$url.'"');

	}

}