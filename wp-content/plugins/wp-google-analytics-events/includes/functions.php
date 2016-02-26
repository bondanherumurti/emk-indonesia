<?php


function migrateOptions() {
	$current_options = get_option('ga_events_options');	
	$new_options = array();
	$new_options['id'] = $current_options['id'];
	$new_options['exclude_snippet'] = $current_options['exclude_snippet'];
	$new_options['universal'] = $current_options['universal'];
	$new_options['events'] = array();
	for ($i=0; $i < sizeof($current_options['divs']); $i++) {
		$current_option = $current_options['divs'][$i];
		$event = new Event('scroll', $current_option[0], $current_option[1], $current_option[2], $current_option[3], $current_option[4]);
		array_push($new_options['events'], $event->getEventArray() );
	}
	
	for ($i=0; $i < sizeof($current_options['click']); $i++) {
		$current_option = $current_options['click'][$i];
		$event = new Event('click', $current_option[0], $current_option[1], $current_option[2], $current_option[3], $current_option[4]);
		array_push($new_options['events'], $event->getEventArray() );
	}

	update_option('ga_events_options', $new_options);
//	print(var_dump($new_options));
}


function isOptionMigrationRequired(){
	$current_options = get_option('ga_events_options');
	if (array_key_exists('divs', $current_options) || array_key_exists('clicks', $current_options)) {
		return true;
	}
	return false;
}

?>
