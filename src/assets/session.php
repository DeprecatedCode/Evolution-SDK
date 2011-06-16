<?php

class Session {
	
	public $data = array();
	private $odata = array();
	public $flashdata = array();
	public $key = false;
	public $id = false;
	public $hits = 0;
	public $member = false;
	
	public function data($key = false) {
		$args = func_get_args();
		$data = $this->data;
		foreach($args as $arg) {
			$data = $data[$arg];
		}
		return $data;
	}
	
	public function __construct() {
		//var_dump($_SERVER);
		//echo 'please return later...'."\r\n";
		if(!($key = $this->_get_key())) {
			$s = $this->_create();
		} else {
			$s = e::db()->select('_sessions', "WHERE `key` = '$key'")->row();
			if(!$s) {
				$s = $this->_create();
			}
		}
		$this->_init($s);
		$this->_init_wildfire();
	}
	
	/**
	 * Synchronize with logged in wildfire user.
	 *
	 * @return void
	 * @author David Boskovic
	 */
	private function _init_wildfire() {

		# initialize the wildfire api helper
		$wildfire = e::helper('wildfire', WILDFIRE_API_KEY, 'compassion');
		
		# load the UID from the cookie if available
		$uid = $wildfire->cookie_has_user($_COOKIE['WILDFIREID']);
		
		# if our logged in user is linked to wildfire but there's no wildfire cookie, that means they're logged out and also should be here
		if(!$uid && $this->member && $this->member->_wildfire_uid) $this->logout();
		
		# otherwise we should just not worry about anything because obviously this isn't a wildfire user
		if(!$uid) return false;
		
		# if our logged in user is already linked up to wildfire, we're cool and don't need to do any UI checks
		if($this->member && $this->member->_wildfire_uid) return true;

		# attempt to load the profile
		$profile = $wildfire->get_supporter($uid);
		if(!$profile) return false;
		
		# if we don't, let's make sure that we have an account for the wildfire user and log them in
		if(!$this->member) {
			
			# try to load up a user that's been linked to the WF ID
			$maybe_user = e::db()->select('members_account', "WHERE `_wildfire_uid` = '$uid' LIMIT 1")->row();
			$maybe_email = e::db()->select('members_account', "WHERE `email` = '$profile->email' LIMIT 1")->row();
			
			# if our user for this wildfire account exists, let's log them in and continue on our merry way
			if($maybe_user) {
				//var_dump($maybe_user['id']);
				$this->login($maybe_user['id']);
				return true;
			}
			
			elseif($maybe_email) {
				$this->login($maybe_email['id']);
				$this->member->_wildfire_uid = $uid;
				return true;
			}
			
			# otherwise, create a new user account and log this user in.
			else {
				$new_member = e::app('members')->account();
				$new_member->email = $profile->email;
				$new_member->first_name = $profile->firstName;
				$new_member->last_name = $profile->lastName;
				$new_member->work = $profile->company;
				$new_member->gender = $profile->gender;
				$new_member->_wildfire_uid = $uid;
				$new_member->model->save();
				$this->login($new_member->id);	
				return true;			
			}
		}
		
		# otherwise let's just attach the wildfire ID to the currently logged in user and not blink an eye.
		else {
			$this->member->_wildfire_uid = $uid;
			return true;
		}
		
		return false;
	}
	
	private function _create() {
		if(strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'facebook') !== false)
			return null;
			
		# get a secure and random token
		$key = $this->_token(32);

		# create the session in the database
		$session = e::db()->insert('_sessions', array('key'=>$key, 'extra_info' =>base64_encode(serialize($_SERVER)), 'data' => base64_encode(serialize(array()))));
		$s = e::db()->select('_sessions', "WHERE `key` = '$key'")->row();

		$cache = cache::get('settings', 'general');
		
		setcookie('_momentum_session', $key,time()+60*60*24*30,'/',MODE_DEVELOPMENT ? false : $cache['cookie_url'],false, false);

		return $s;
	}
	
	private function _init($session) {
		if(is_null($session))
			return;
		$this->key = $session['key'];
		$this->id = $session['id'];
		$this->data = unserialize(base64_decode($session['data']));
		if($session['members_account_id'] > 0) {
			$this->member = e::app('members')->account($session['members_account_id']);
			$this->data['member_id'] = $this->member->id;
		} else {
			$this->data['member_id'] = false;
		}
		$this->odata = md5($session['data']);
		$this->flashdata = isset($this->data['flashdata']) ? $this->data['flashdata'] : array();
		if($_GET['_theme']) $this->data['theme_name'] = $_GET['_theme'];
		
		$ts = cache::get('settings',"template");
		$t = isset($ts['theme']) ? $ts['theme'] : DEFAULT_THEME;
		$t = isset($this->data['theme_name']) ? $this->data['theme_name'] : $t;
		
		define('ACTUAL_THEME', $t);
	}
	
	private function _get_key() {
		# Allow session overriding for methods that cannot send the correct cookie (i.e. Flash)
		if(isset($_POST['_e_override_session']))
			$_COOKIE['_momentum_session'] = $_POST['_e_override_session'];
			
		if(!isset($_COOKIE['_momentum_session'])) return false;
		$key = $_COOKIE['_momentum_session'];
		if(strlen($key) == 32) return $key;
		else return false;
	}
	
	private function _ssl() {
		
	}
	
	public function login($id) {
		if(!$id) return false;
		$session = e::db()->query("UPDATE _sessions SET `members_account_id` = '$id' WHERE `id` = '$this->id'");
		$this->member = e::app('members')->account($id);
	}
	
	public function logout() {
		$session = e::db()->query("UPDATE _sessions SET `members_account_id` = '0' WHERE `id` = '$this->id'");
		$this->member = false;
	}
	
	public function has_member() {
		if($this->member !== false)
			return true;
		return false;
	}
	
	public function flashdata_push($key, $subkey, $value) {
		$this->data['flashdata'][$key][$subkey][] = $value;
	}
	
	public function message($type, $message) {
		return $this->flashdata_push('result_data', 'messages', array('type' => $type, 'message' => $message));
	}
	
	public function flashdata($key = false, $value = false) {
		
		if($value !== false) {
			$this->data['flashdata'][$key] = $value;
			return true;
		}
		else {
			//var_dump($this->data['flashdata']);
			
			if(isset($this->data['flashdata'][$key])) {
				unset($this->data['flashdata'][$key]); 
				// Logging class initialization
				$log = new Logging('flashdata.txt');
				// write message to the log file
				$log->lwrite($_SERVER['REQUEST_URI'].' - Deleted flashdata.');
			}
			return $this->flashdata[$key];
		}
	}
	
	public function __destruct() {
		# add a hit
		$session = e::db()->insert('_hits', array('session_id'=>$this->id, 'url' =>$_SERVER['REQUEST_URI'], 'referrer' => $_SERVER['HTTP_REFERER']));
		
		# save session
		$ser = base64_encode(serialize($this->data));
		//var_dump("UPDATE _sessions SET `data` = '$ser', `hits`=`hits`+1 WHERE `id` = '$this->id'");
		if(md5($ser) != $this->odata)
			$session = e::db()->query("UPDATE _sessions SET `data` = '$ser', `hits`=`hits`+1 WHERE `id` = '$this->id'");
		else {
			$session = e::db()->query("UPDATE _sessions SET `hits`=`hits`+1 WHERE `id` = '$this->id'");			
		}
		
	}
	
	/**
	 * Generate a random session ID.
	 *
	 * @param string $len 
	 * @param string $md5 
	 * @return void
	 * @author Andrew Johnson
	 * @website http://www.itnewb.com/v/Generating-Session-IDs-and-Random-Passwords-with-PHP
	 */
	private function _token( $len = 32, $md5 = true ) {

	    # Seed random number generator
	    # Only needed for PHP versions prior to 4.2
	    mt_srand( (double)microtime()*1000000 );

	    # Array of characters, adjust as desired
	    $chars = array(
	        'Q', '@', '8', 'y', '%', '^', '5', 'Z', '(', 'G', '_', 'O', '`',
	        'S', '-', 'N', '<', 'D', '{', '}', '[', ']', 'h', ';', 'W', '.',
	        '/', '|', ':', '1', 'E', 'L', '4', '&', '6', '7', '#', '9', 'a',
	        'A', 'b', 'B', '~', 'C', 'd', '>', 'e', '2', 'f', 'P', 'g', ')',
	        '?', 'H', 'i', 'X', 'U', 'J', 'k', 'r', 'l', '3', 't', 'M', 'n',
	        '=', 'o', '+', 'p', 'F', 'q', '!', 'K', 'R', 's', 'c', 'm', 'T',
	        'v', 'j', 'u', 'V', 'w', ',', 'x', 'I', '$', 'Y', 'z', '*'
	    );

	    # Array indice friendly number of chars; empty token string
	    $numChars = count($chars) - 1; $token = '';

	    # Create random token at the specified length
	    for ( $i=0; $i<$len; $i++ )
	        $token .= $chars[ mt_rand(0, $numChars) ];

	    # Should token be run through md5?
	    if ( $md5 ) {

	        # Number of 32 char chunks
	        $chunks = ceil( strlen($token) / 32 ); $md5token = '';

	        # Run each chunk through md5
	        for ( $i=1; $i<=$chunks; $i++ )
	            $md5token .= md5( substr($token, $i * 32 - 32, 32) );

	        # Trim the token
	        $token = substr($md5token, 0, $len);

	    } return $token;
	}
}


