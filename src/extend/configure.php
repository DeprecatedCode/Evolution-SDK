<?php

/**
 * Load configuration files from yaml, xml, or cache depending on configuration and 
 *
 * @package default
 * @author David Boskovic
 */
class e_Configure extends e_Loader {
	

	protected function _get_configuration($library,$override=false) {
		if(file_exists(SUPER_ROOT_CONFIGURE."/$library".'.yaml') && !$override)
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
		$override=false;
		if(strpos($file,'_local')){
			$override=true;
			$file=str_replace('_local','',$file);
		}
		$key = md5($this->_dir.$file);
		# load the environments
		if(e::$cache->check('engine_configure', $key)) {
			$result = e::$cache->get('engine_configure', $key);
		}
		if(!$result || ($this->_get_configuration_time($file) > e::$cache->timestamp('engine_configure', $key))) {
			$result = $this->_get_configuration($file,$override);
			e::$cache->store('engine_configure', $key, $result, 'base64');
		}
		return $result;
		
	}
	
	/**
	 * Load the language data.
	 *
	 * @return array
	 * @author Nate Ferrero
	 */
	public function get_language_data($level = false) {
		
		# Set the current theme
		
		$ts = cache::get('settings',"template");
		$t = isset($ts['theme']) ? $ts['theme'] : DEFAULT_THEME;
		$t = isset(e::$session->data['theme_name']) ? e::$session->data['theme_name'] : $t;
		
		define('ACTUAL_THEME', $t);
		
		# modify this to get the language code from some source
		$language = 'en';
		
		# YAML Filename
		$l = '/language/' . $language . '/default.yaml';
		
		$s = $this->_dir . $l;
		$data = file_exists($s) ? e::helper('yaml')->file($s) : array();
		if($level == 'core')
			return $data;
			
		$s = SUPER_ROOT_CONFIGURE . $l;
		$custom = file_exists($s) ? e::helper('yaml')->file($s) : array();
		if($level == 'custom')
			return $custom;
		
		$this->extend_lang($data, $custom);
		
		return $data;
	}
	
	/**
	 * Save a language file.
	 *
	 * @return void
	 * @author Nate Ferrero
	 */
	public function save_language_data($level, $yaml) {
		# modify this to get the language code from some source
		$language = 'en';
		
		# convert to YAML
		if(!is_string($yaml))
			$yaml = e::$helper->yaml->dump($yaml, 4, 0, true);
		
		$yaml = str_replace(" \n", "\n", $yaml);
		$yaml = str_replace("\n                ", "\n\t\t\t\t", $yaml);
		$yaml = str_replace("\n            ", "\n\t\t\t", $yaml);
		$yaml = str_replace("\n        ", "\n\t\t", $yaml);
		$yaml = str_replace("\n    ", "\n\t", $yaml);
		$yaml = preg_replace('/\n(\w)/', "\n\n$1", $yaml);
		
		# Add comments
		$yaml = "#
#	YAYLang - Yet Another YAML Language [Markup]
#
#	Documentation: http://edge.momentumapp.co/--docs/yaylang
#
" . $yaml;
		# YAML Filename
		$l = '/language/' . $language . '/default.yaml';
		
		$s = $this->_dir . $l;
		$GLOBALS['lang_file'] = $s;
		if($level == 'core')
			return force_file_put_contents($s, $yaml);
			
		$s = SUPER_ROOT_CONFIGURE . $l;
		$GLOBALS['lang_file'] = $s;
		if($level == 'custom')
			return force_file_put_contents($s, $yaml);
	}
	
	/**
	 * Extend the language array with new information
	 */
	public function extend_lang(&$data, $new) {
		foreach($new as $key => $value) {
			if(!is_array($data[$key]))
				$data[$key] = $value;
			else if(is_array($value)) {
				$this->extend_lang($data[$key], $value);
			} else {
				$data[$key]['title'] = $value;
			}
		}
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
		
		# get postmark data
		$postmark = $this->postmark;
		define('POSTMARKAPP_API_KEY', element($postmark, 'api_key'));
		define('POSTMARKAPP_MAIL_FROM_NAME', element($postmark, 'from_name'));
		define('POSTMARKAPP_MAIL_FROM_ADDRESS', element($postmark, 'from_address'));
		
		# set the theme
		if(isset($env['theme']))			
			define('DEFAULT_THEME',$env['theme']);
		else	
			define('DEFAULT_THEME','minimal');
		
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