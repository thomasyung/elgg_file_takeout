<?php

elgg_register_event_handler('init', 'system', 'file_takeout_init');

function file_takeout_init() {
	elgg_register_page_handler('file_takeout','page_handler_file_takeout');
	elgg_register_page_handler('file_takeout_download', 'page_handler_file_takeout_download');
}

function page_handler_file_takeout($page) {
	include elgg_get_plugins_path() . 'file_takeout/file_takeout.php';
}

function page_handler_file_takeout_download($page) {
	$file_guid = $page[0];
	$file_name = $file_guid.'.zip';
	$file_path = elgg_get_data_path();
	if (file_exists($file_path.$file_name)){
		$mime = "application/octet-stream";
		header("Pragma: public");
		header("Content-type: $mime");
		header("Content-Disposition: attachment; filename=\"$file_name\"");
		ob_clean();
		flush();
		readfile($file_path.$file_name);
		exit;
	} else {
		register_error(elgg_echo("file:downloadfailed"));
		forward('/file_takeout');
	}
}

?>