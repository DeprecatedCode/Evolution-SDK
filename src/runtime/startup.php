<?php

/**
 * Runtime Startup file.
 * 
 * @author David Boskovic
 * @package evolution
 */

define('START_TIME', microtime(true));



/**
 * Load Functions
 */
include('functions.php');


/**
 * Define ROOT_ variables.
 */
define('ROOT', substr(dirname(__FILE__),0,-19));
define('ROOT_LIBRARY', ROOT.'/library');
define('ROOT_HELPERS', ROOT.'/library/helpers');
define('ROOT_CACHE', ROOT.'/library/cache');
define('ROOT_FRAMEWORK', ROOT.'/_evolution');
define('ROOT_INTERFACE', ROOT.'/interface');
define('ROOT_COMPONENTS', ROOT.'/components');
define('ROOT_CONTROLLERS', ROOT.'/controllers');
define('ROOT_CONFIGURE', ROOT.'/configure');
define('ROOT_PORTALS', ROOT.'/portals');
define('ROOT_THEME', ROOT.'/themes');

define('ROOT_APPLICATIONS', ROOT.'/applications');
define('ROOT_ACTIONS', ROOT.'/actions');

/**
 * Define SUPER_ROOT_ variables.
	 */
$tmp=explode(DIRECTORY_SEPARATOR,ROOT);
array_pop($tmp);
define('SUPER_ROOT',implode(DIRECTORY_SEPARATOR,$tmp));
define('SUPER_ROOT_CONFIGURE',SUPER_ROOT.'/configure');
define('SUPER_ROOT_THEME',SUPER_ROOT.'/theme');
define('SUPER_ROOT_LOG',SUPER_ROOT.'/log');
define('SUPER_ROOT_EXTEND',SUPER_ROOT.'/extend');
define('SUPER_ROOT_INTERFACE',SUPER_ROOT.'/interface');
define('SUPER_ROOT_MEDIA',SUPER_ROOT.'/media');
/**
 * @todo DEPRECATE THESE
 */
define('DEFAULT_SITE', 0);
define('PAGINATE_COUNT', 15);
define('MAX_INVITE_COUNT',5);
define('SITE_DEFAULT_PASSWORD','changeme');
define('SITE_LOCATION', dirname(__FILE__).'/../..');

define('CONTACT_EMAIL','josh.jessup@gmail.com');
define('NEXT_FAST_DATE',first_day_of_month());
@include(ROOT . '/_config.php');

/**
 * Exception Handler
 */
include('exceptions.php');

/**
 * Make sure that specific directories are writable.
 */
if(!is_writable(ROOT_CACHE)) {
	die('no permission to write to cache.');
	//e::fault(10,'write_to_cache');
}


/**
 * Load up the framework
 */
require ROOT_FRAMEWORK."/_base.php";

# load up all the applications
$d = dir(ROOT_APPLICATIONS); 
while (false!== ($filename = $d->read())) { 
	if(substr($filename,0,1) !='.') {
		if(!file_exists(ROOT_APPLICATIONS."/$filename/_application.php") || !file_exists(ROOT_APPLICATIONS."/$filename/configure/settings.yaml")) {	
			e::fault(10,'application_not_configured', array('app' => $filename));
		}
		else {
			# load up settings
			$conf = e::helper('yaml')->file(ROOT_APPLICATIONS."/$filename/configure/settings.yaml");
			if(isset($conf['hooks']['incoming'])) {
				foreach($conf['hooks']['incoming'] as $hook) {
					e::$_deprecated_cache['module_hooks'][$hook][$filename] = true;
				}
			}
			if(isset($conf['hooks']['outgoing'])) {
				foreach($conf['hooks']['outgoing'] as $key => $hook) {
					foreach($hook as $item) {
						e::$_deprecated_cache['module_hooks'][$filename.'.'.$key][$item] = true;
					}
				}
			}
		}
		
	}  
}

$d->close();


/**
 * Setup autoloading of necessary PHP classes.
 */
include('autoloader.php');

/**
 * Initialize Evolution, Load Configurations, Start Database, Etc.
 */
e::init();




/**
 * Trigger the runtime startup event.
 */
e::$event->trigger('runtime.startup');

include('shutdown.php');