<?php

/**
 * Handles upgrading.
 *
 * @package default
 * @author David Boskovic
 */
class UpgradeScript {
	
	protected $versions = array();
	
	/**
	 * Executed by the API call.
	 *
	 * @param string $version 
	 * @return json
	 * @author David Boskovic
	 */
	public function run($version, $undo = false) {
		$has_run = cache::get('utilities','upgrades');
		if(@$has_run[$this->slug][$version] AND !$undo AND !$_GET['_reset']) return json_encode('You have already run this upgrade.');
		if(!@$has_run[$this->slug][$version] AND $undo) return json_encode('You can\'t undo this upgrade because you either haven\'t run the upgrade or you\'ve already undone it.');
		if(!$this->versions[$version]) return json_encode(false);
		else {
			$a = array();
			if(!$undo)
				foreach($this->versions[$version] as $method => $description) {
					if($method == '_undo') continue;
					$a[$method] = array('action' => $description, 'result' => $this->$method());
					$vr = true;
				}
			else {
				$method = $this->versions[$version]['_undo'];
				$a[$method] = array('result' => $this->$method());
				$vr = false;
			}
			
			$has_run[$this->slug][$version] = $vr;
			cache::store('utilities', 'upgrades', $has_run);
		}
		return json_encode($a);
	}
	
	public function get_versions() {
		$has_run = cache::get('utilities','upgrades');
		$v = array();
		foreach($this->versions as $version => $contents) {
			$v[] = array('version' => $version, 'contents' => $contents, 'has_run' => @$has_run[$this->slug][$version]);
		}
		return $this->versions ? $v : array(array('version' => 'No Versions'));
	}
	
	protected function _get_between($t, $start, $end, $reverse = 0) {
		//var_dump($reverse);
		$s = strpos($t, $start)+strlen($start);
		$l = strlen($t);
		$e = $reverse ? strrpos($t, $end)-strlen($end) : strpos($t, $end,$s);
	
		$r = substr($t, $s, $l-($l-$e)-$s);
		return $r;
	}
}