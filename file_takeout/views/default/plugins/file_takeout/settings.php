<?php
/**
 * File Takeout plugin settings
 */

// set default value
if (!isset($vars['entity']->file_takeout_site_menu)) {
	$vars['entity']->file_takeout_site_menu = 'no';
}

echo '<div>';
echo '<p>This tool allows you to take out all your files and blogs that you own into a ZIP archive. You can use that ZIP archive as a backup and restore on some other external system.</p>';
echo '<p style="font-style: italic;">NOTE: Users can access File Takeout from their Settings page sidebar menu. You can set <b>Show site menu</b> to Yes, if you want to additionally show File Takeout on the site menu.</p>';
echo elgg_echo('Show site menu?');
echo ' ';
echo elgg_view('input/select', array(
	'name' => 'params[file_takeout_site_menu]',
	'options_values' => array(
		'no' => elgg_echo('option:no'),
		'yes' => elgg_echo('option:yes')
	),
	'value' => $vars['entity']->file_takeout_site_menu,
));
echo '</div>';

?>