<?php

/**
 * Load configuration files from yaml, xml, or cache depending on configuration and 
 *
 * @package default
 * @author David Boskovic
 */
class e_Configure extends e_Loader {
	

	protected function _get_configuration($library) {
		if(file_exists(SUPER_ROOT_CONFIGURE."/$library".'.yaml'))
			return e::helper('yaml')->file(SUPER_ROOT_CONFIGURE."/$library".'.yaml');
		else
			return e::helper('yaml')->file($this->_dir.'/'.$library.'.yaml');
	}

	protected function _get_configuration_time($library) {
		
		if(file_exists(SUPER_ROOT_CONFIGURE."/$library".'.yaml'))
			return @filemtime(SUPER_ROOT_CONFIGURE.'/'.$library.'.yaml');
		else
			return @filemtime($this->_dir.'/'.$library.'.yaml');
	}
	
	public function __get($file) {
		$key = md5($this->_dir.$file);
		# load the environments
		if(e::$cache->check('engine_configure', $key)) {
			$result = e::$cache->get('engine_configure', $key);
		}
		if(!$result || ($this->_get_configuration_time($file) > e::$cache->timestamp('engine_configure', $key))) {
			$result = $this->_get_configuration($file);
			e::$cache->store('engine_configure', $key, $result, 'base64');
		}
		return $result;
		
	}
	
	/**
	 * Load in the environment configuration.
	 *
	 * @return void
	 * @author David D. Boskovic
	 */
	public function get_environment_data() {
		
		# trigger event
		e::$event->trigger('engine.configure_environment.before');
		
		# load the environments		
		$env = $this->_find_environment($this->environments);
		
		# if no matching environment is found we can't keep going.
		if(!$env)
			die('<h3>'.e::$configure->license['name'].'</h3>There is no matching environment configuration for this server.
				<p>Please add an appropriate configation in <code>' . htmlspecialchars(realpath(dirname(__FILE__) . '/../../')) . '/configure/environments.yaml</code></p>');
		
		# make sure we have database configuration.
		if(!isset($env['database']))
			die('You need to add database configuration to your environment.');
		
		# decide if we're in development mode.
		if($env['mode'] == 'development')			
			define('MODE_DEVELOPMENT', true);
		else	
			define('MODE_DEVELOPMENT', false);
		
		# trigger event
		e::$event->trigger('engine.configure_environment.after');	
		
		# save the environment configuration to the object.
		return $env;		
	}
	
	/**
	 * Analyze the environment configurations to find the right env.
	 *
	 * @param string $environments 
	 * @return void
	 * @author David D. Boskovic
	 */
	private function _find_environment($environments) {
		$use_environment = false;
		//die(var_dump(url::$domain));
		if(is_array($environments)) {
			foreach($environments as $key => $env) {
				if(isset($env['use_on'])) {
					$use=true;
					
					$user = '';
					if(isset($_ENV['USER']))
						$user = $_ENV['USER'];
					else if(isset($_ENV['USERNAME']))
						$user = $_ENV['USERNAME'];
					
					
					if(isset($env['use_on']['env_user']) && $env['use_on']['env_user'] != $user) 
						$use = false;
					if(isset($env['use_on']['top_level_domain']) && strrpos($_SERVER['SERVER_NAME'],$env['use_on']['top_level_domain']) === false)
						$use = false;
				}
				else {
					$use = true;
				}	
				if($use) {
					$use_environment = $env;
					break;
				}
			}
		}
		return $use_environment;
	}
}