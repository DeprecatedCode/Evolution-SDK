<?php

/**
 * User Management Component
 *
 * @package default
 * @author David Boskovic
 */
class Component_Users extends Component {
	
	/**
	 * Name of Component
	 */
	protected $_name = 'users';
	
	/**
	 * Default lists.
	 */
	protected $_lists = array(
		'list_accounts' => 'users.account'
	);
	
	/**
	 * Default items.
	 */
	protected $_models = array(
		'account' => 'users.account',
		'get_account' => 'users.account'
	);
	
	/**
	 * Authenticate a logged in user.
	 *
	 * @param string $email 
	 * @param string $password 
	 * @return boolean
	 * @author David Boskovic
	 */
	public function authenticate($email, $password) {
		if(empty($email) || empty($password)) return false;
		$email = strtolower($email);
		$member = e::$db->mysql->select('users_account', "WHERE email='%s' AND password='%s'", array($email, $password))->row();	
		if($member)
			return $this->get_account($member['id']);
		else
			return false;
	}
	
	
}
