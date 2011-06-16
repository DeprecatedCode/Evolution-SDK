<?php

/**
 * This is the class autoloader.
 *
 * @return void
 * @author David Boskovic
 **/
if(!is_callable('__autoload')) {
	function __autoload($class_name)
{
	//var_dump(func_get_args());

	# looking for a model
	if(stripos($class_name,'model') === 0) {
		include(ROOT_FRAMEWORK.'/src/component/models/'.strtolower($class_name).'.php');
	}
	
	# looking for an application
	elseif(($mp = stripos($class_name,'__Model_')) > 0) {
		$com = e::$configure->components;
		$m = substr($class_name,$mp+8);
		$com = substr($class_name,0,$mp);
		$a = substr($com, 10);
		include(ROOT_COMPONENTS."/$a/models/$m.php");
	}
	# looking for an application
	elseif(($mp = stripos($class_name,'__LIST_')) > 0) {
		$com = e::$configure->components;
		$m = substr($class_name,$mp+7);
		$com = substr($class_name,0,$mp);
		$a = substr($com, 10);
		include(ROOT_COMPONENTS."/$a/lists/$m.php");
	}
	
	# looking for a controller
	elseif(stripos($class_name,'controller_') === 0) {
		if(!e::$url->portal) die('trying to load a controller without setting the portal');
		if(file_exists(ROOT_PORTALS.'/'.e::$url->portal.'/controllers/'.strtolower(substr($class_name,strlen('controller_'))).'.php'))
			include(ROOT_PORTALS.'/'.e::$url->portal.'/controllers/'.strtolower(substr($class_name,strlen('controller_'))).'.php');
	}
	
	# looking for an action
	elseif(stripos($class_name,'action_') === 0) {
		if(!e::$url->portal) die('trying to load an action without setting the portal');
		if(file_exists(ROOT_PORTALS.'/'.e::$url->portal.'/actions/'.strtolower(substr($class_name,strlen('action_'))).'.php'))
			include(ROOT_PORTALS.'/'.e::$url->portal.'/actions/'.strtolower(substr($class_name,strlen('action_'))).'.php');
	}
	
	
	# looking for an application
	elseif(stripos($class_name,'component_model') === 0) {
		include(ROOT_FRAMEWORK.'/src/component/models/'.strtolower(substr($class_name,strlen('component_'))).'.php');
	}
	
	# looking for an application
	elseif(stripos($class_name,'component_list') === 0) {
		include(ROOT_FRAMEWORK.'/src/component/lists/'.strtolower(substr($class_name,strlen('component_'))).'.php');
	}
	# looking for an application
	elseif(stripos($class_name,'component_') === 0) {
		include(ROOT_COMPONENTS.'/'.strtolower(substr($class_name,strlen('component_'))).'/_component.php');
	}
		
	# looking for an evolution class
	elseif(stripos($class_name,'e_') === 0) {
		$n = strtolower(substr($class_name,strlen('e_')));
		if(file_exists(ROOT_FRAMEWORK."/src/$n.php")) {
			include(ROOT_FRAMEWORK."/src/$n.php");
		}
		elseif(file_exists(ROOT_FRAMEWORK."/src/$n/$n.php")) {
			include(ROOT_FRAMEWORK."/src/$n/$n.php");
		}
		
	}
	else {
		switch($class_name) {
			case 'Component':
				include(ROOT_FRAMEWORK.'/src/component/component.php');
			break;
			case 'Action':
				include(ROOT_FRAMEWORK.'/extend/action.php');
			break;
		}
	}
	//if(!class_exists($class_name)) die("Can't find class: $class_name");
}
}