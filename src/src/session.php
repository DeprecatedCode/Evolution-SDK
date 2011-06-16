<?php

/**
 * Complete Session Management for the Evolution SDK
 *
 * @package evolution
 * @author David Boskovic
 * @license Apache 2.0
 * @copyright Open Source
 */

class e_Session {
	
	/**
	 * This is a variable that makes data available for access and modification,
	 * it will be saved in the sessions table at the end of each page load.
	 *
	 * @var mixed
	 */
	public $data = array();
	
	/**
	 * This stores an md5 hash of the serialized data string so that the session
	 * won't go to the effort of rewriting the session data if it hasn't changed.
	 *
	 * @var string
	 */
	private $_data_hash;
	
	/**
	 * This is quick access to a subset of the session data that is specifically reset
	 * after being accessed once.
	 *
	 * @var mixed
	 */
	public $flashdata = array();
	
	/**
	 * The current valid session key.
	 *
	 * @var string
	 */
	public $key = false;
	
	/**
	 * A link to the PHP cookie variable.
	 *
	 * @var reference
	 */
	public $cookie = false;
	
	/**
	 * The current valid numeric session id.
	 *
	 * @var string
	 */
	public $id = false;
	
	/**
	 * The number of hits that this session has had.
	 *
	 * @var integer
	 */
	public $hits = 0;
	
	/**
	 * Either false, or an instance of the currently logged in member's model.
	 *
	 * @var string
	 */
	public $member = false;
	
	
	
	
	/**
	 * Create a session or initialize a new one.
	 *
	 * @author David Boskovic
	 */
	public function __construct() {
	
		# determine the proper cookie name
		$cn = element(cache::get('settings','general'), 'cookie_name', '_momentum_session');
		define('EVOLUTION_SESSION_COOKIE', $cn);
	
		# link the cookie
		$this->cookie = &$_COOKIE[$cn];
		
		# check to see if the browser is passing a session key and if not, create a new session
		if(!($key = $this->_get_key())) {
			$s = $this->_create();
		} else {
			# we can urlencode the key because it ought to be all alphanumeric characters
			$key = urlencode($key);
			
			# make sure the session key being passed is valid
			$s = e::db()->select('_sessions', "WHERE `key` = '$key'")->row();
			if(!$s) {
				# otherwise create the session
				$s = $this->_create();
			}
		}
		
		# now that we have a valid session, run the environment initialize
		$this->_init($s);
		
		/**
		 * WARNING THIS IS A HACK AND SHOULD NOT BE PERMANENT
		 * @todo make this an event plugin	
		 * @author David Boskovic
		 */
		$c = cache::get('settings','general');
		if($c['wildfire'])
			$this->_init_wildfire();
	}
	
	/**
	 * An odd method for retrieving data from the session variable. This needs to be reconsidered from
	 * an engineering and optimization perspective.
	 *
	 * @param string $key 
	 * @return mixed
	 * @author David Boskovic
	 * @todo review for necessity, benefit, and speed
	 */
	public function data($key = false) {
		$args = func_get_args();
		$data = $this->data;
		foreach($args as $arg) {
			$data = $data[$arg];
		}
		return $data;
	}
	
	/**
	 * Synchronize with logged in wildfire user.
	 *
	 * @return void
	 * @author David Boskovic
	 * @todo remove and make this into a proper session extension rather than a built in method
	 */
	private function _init_wildfire() {

		# initialize the wildfire api helper
		$wildfire = e::helper('wildfire', WILDFIRE_API_KEY, 'compassion');
		
		# load the UID from the cookie if available
		$uid = $wildfire->cookie_has_user($_COOKIE['WILDFIREID']);
		
		# if our logged in user is linked to wildfire but there's no wildfire cookie, that means they're logged out and also should be here
		if(!$uid && $this->member && $this->member->_wildfire_uid) {
			$wildfire_member = $wildfire->get_supporter_by_email($this->member->email)->supporters[0];
			if($wildfire_member)
				$this->logout();
			else
				$this->member->_wildfire_uid = false;
		}

		# if we have a currently logged in user that is not a wildfire account, we need to synchronize this user with wildfire
		if(!$uid && $this->member && !$this->member->_wildfire_uid) {

			# search wildfire to see if this email has already been used for an account
			$wildfire_member = $wildfire->get_supporter_by_email($this->member->email)->supporters[0];
			
			# if we have a wildfire member already, let's connect the accounts.
			if($wildfire_member) $this->member->_wildfire_uid = $wildfire_member->id;
			
			# otherwise let's create an account on wildfire that mirrors this one
			else {
				$wildfire_member = $wildfire->add_supporter($this->member);
				$this->member->_wildfire_uid = $wildfire_member->id;
			}
			
			# continue our script and save the uid into the temp variable
			$uid = $this->member->_wildfire_uid;
		}

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
	
	/**
	 * Create a new session.
	 *
	 * @return void
	 * @author David Boskovic
	 */
	private function _create() {
		if(strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'facebook') !== false)
			return null;
			
		# get a secure and random token
		$key = $this->_token(32);

		# create the session in the database
		$session = e::db()->insert('_sessions', array('key'=>$key, 'extra_info' =>base64_encode(serialize($_SERVER)), 'data' => base64_encode(serialize(array()))));
		$s = e::db()->select('_sessions', "WHERE `key` = '$key'")->row();

		$cache = cache::get('settings', 'general');
		
		setcookie(EVOLUTION_SESSION_COOKIE, $key,time()+60*60*24*30,'/',MODE_DEVELOPMENT ? false : $cache['cookie_url'],false, false);

		return $s;
	}
	
	/**
	 * Initialize this session, load the logged in member, unserialize session data
	 * load flash data, and some other random shit that shouldn't be happening here.
	 *
	 * @param string $session 
	 * @return void
	 * @author David Boskovic
	 */
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
		$this->_data_hash = md5($session['data']);
		$this->flashdata = isset($this->data['flashdata']) ? $this->data['flashdata'] : array();
		if($_GET['_theme']) $this->data['theme_name'] = $_GET['_theme'];
	}
	
	/**
	 * Get the session from the cookie name.
	 *
	 * @todo use the properly configured session name
	 * @return void
	 * @author David Boskovic
	 */
	private function _get_key() {
		# Allow session overriding for methods that cannot send the correct cookie (i.e. Flash)
		if(isset($_POST['_e_override_session']))
			$_COOKIE[EVOLUTION_SESSION_COOKIE] = $_POST['_e_override_session'];
			
		if(!isset($_COOKIE[EVOLUTION_SESSION_COOKIE])) return false;
		$key = $_COOKIE[EVOLUTION_SESSION_COOKIE];
		if(strlen($key) == 32) return $key;
		else return false;
	}
	
	/**
	 * Log a user into the current session. No validation here. Please validate in separate login action.
	 *
	 * @param string $id 
	 * @return void
	 * @author David Boskovic
	 */
	public function login($id) {
		if(!$id) return false;
		$session = e::db()->query("UPDATE _sessions SET `members_account_id` = '$id' WHERE `id` = '$this->id'");
		$this->member = e::app('members')->account($id);
	}
	
	/**
	 * Expire the current session so that the user is no longer logged in.
	 *
	 * @return void
	 * @author David Boskovic
	 */
	public function logout() {
		$session = e::db()->query("UPDATE _sessions SET `members_account_id` = '0' WHERE `id` = '$this->id'");
		$this->member = false;
	}
	
	/**
	 * Check to see if this session has a logged in member.
	 *
	 * @return void
	 * @author David Boskovic
	 */
	public function has_member() {
		if($this->member !== false)
			return true;
		return false;
	}
	
	/**
	 * Add a flashdata variable
	 *
	 * @param string $key 
	 * @param string $subkey 
	 * @param string $value 
	 * @return void
	 * @author David Boskovic
	 */
	public function flashdata_push($key, $subkey, $value) {
		$this->data['flashdata'][$key][$subkey][] = $value;
	}
	
	/**
	 * Add a message to the flashdata. This is weird too.
	 *
	 * @param string $type 
	 * @param string $message 
	 * @return void
	 * @author David Boskovic
	 */
	public function message($type, $message) {
		return $this->flashdata_push('result_data', 'messages', array('type' => $type, 'message' => $message));
	}
	
	/**
	 * Add or access flashdata.
	 *
	 * @param string $key 
	 * @param string $value 
	 * @return void
	 * @author David Boskovic
	 */
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
	
	/**
	 * Save stuff on page shutdown.
	 *
	 * @return void
	 * @author David Boskovic
	 */
	public function __destruct() {
		# add a hit
		$session = e::db()->insert('_hits', array('session_id'=>$this->id, 'url' =>$_SERVER['REQUEST_URI'], 'referrer' => $_SERVER['HTTP_REFERER']));
		
		# save session
		$ser = base64_encode(serialize($this->data));
		//var_dump("UPDATE _sessions SET `data` = '$ser', `hits`=`hits`+1 WHERE `id` = '$this->id'");
		if(md5($ser) != $this->_data_hash)
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

/**
 * Logging class:
 * - contains lopen and lwrite methods
 * - lwrite will write message to the log file
 * - first call of the lwrite will open log file implicitly
 * - message is written with the following format: hh:mm:ss (script name) message
 * @todo why the fuck is this here?
 */
class Logging{
	// define log file
	private $log_file = '';
	// define file pointer
	private $fp = null;
	public function __construct($file = 'errors.txt') {
		$this->log_file = ROOT_LIBRARY.'/logs/'.$file;
	}
	// write message to the log file
	public function lwrite($message){
		// if file pointer doesn't exist, then open log file
		if (!$this->fp) $this->lopen();
		// define script name
		$script_name = pathinfo($_SERVER['PHP_SELF'], PATHINFO_FILENAME);
		// define current time
		$time = date('H:i:s');
		// write current time, script name and message to the log file
		fwrite($this->fp, "$time ($script_name) $message\n");
	}
	// open log file
	private function lopen(){
		// define log file path and name
		$lfile = $this->log_file;
		// define the current date (it will be appended to the log file name)
		$today = date('Y-m-d');
		// open log file for writing only; place the file pointer at the end of the file
		// if the file does not exist, attempt to create it
		$this->fp = fopen($lfile . '_' . $today, 'a') or exit("Can't open $lfile!");
	}
}
