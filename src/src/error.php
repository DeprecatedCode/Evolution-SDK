<?php

class e_Error {
	public function fault($event, $key, $data = array(), $custom_error = false) {

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
		echo "<div style='font-family:courier new; font-size: 14px; white-space:pre;'>";
		echo $message."\n";
		var_dump(func_get_args());
		die;
	}
}