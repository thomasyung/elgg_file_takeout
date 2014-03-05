<?php

global $CONFIG;

require_once($CONFIG->path . "/engine/start.php");

$site_url =  elgg_get_site_url();

$title = elgg_view_title("File Takeout");

$guid_from_path = basename($_SERVER["REQUEST_URI"]);

$logged_in_user = elgg_get_logged_in_user_entity();

// Create the ZIP archive and make it available for download
if ($guid_from_path != 'file_takeout') {
	$file_options = array(
		'type' => 'object',
		'subtype' => 'file',
		'container_guid' => $guid_from_path,
		'limit' => '',
	);
	$files = elgg_get_entities($file_options);
	$blog_options = array(
		'type' => 'object',
		'subtype' => 'blog',
		'container_guid' => $guid_from_path,
		'limit' => '',
	);
	$blogs = elgg_get_entities($blog_options);
	if (count($files) > 0 || count($blogs) > 0) {
		$area .= '<br><p>Zipping the following files...</p>';
		$area .= '<ul>';
		$archive_path = elgg_get_data_path() . $guid_from_path . '.zip';
		if (file_exists($archive_path)) {
			unlink($archive_path);
		}
		$zip = new ZipArchive;
		$res = $zip->open($archive_path, ZipArchive::CREATE);
		if ($res === TRUE) {
			foreach ($files as $file) {
				$area .= '<li style="font-family: courier, monospace;">...' . $file->originalfilename . '</li>';
				$zip->addFile($file->getFilenameOnFilestore(), $file->originalfilename);
			}
			if (count($blogs) > 0) {
				$area .= '<li style="font-family: courier, monospace;">...blog_entries.xml</li>';
				$group_entity = get_entity($guid_from_path);
				$blog_url = $site_url . 'blog/group/' . $guid_from_path . '/all';
				set_input('view', 'rss');
				$blog_contents = <<<__HTML
<rss version="2.0" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:georss="http://www.georss.org/georss" >
<channel>
	<title><![CDATA[$group_entity->name blog]]></title>
	<link>$blog_url</link>
	<description><![CDATA[]]></description>

__HTML;
				$blog_contents .= elgg_list_entities($blog_options);
				$blog_contents .= '</channel></rss>';
				set_input('view', 'default');
				$zip->addFromString('blog_entries.xml', $blog_contents);
				// Experimental -- create a file for each blog entry
				foreach ($blogs as $blog) {
					$blog_author = get_entity($blog->owner_guid)->name;
					$blog_pubdate = date('r', $blog->time_created);
					$blog_filedate = date('Y-m-d', $blog->time_created);
					$blog_content = <<<__HTML
<!DOCTYPE html>
<head>
<title>$blog->title</title>
</head>
<body>
<article class="post">
<header>
<h1>$blog->title</h1>
<h4 class="post-date">By $blog_author on <time datetime="$blog_pubdate">$blog_pubdate</time></h4>
</header>
$blog->description

__HTML;
					if ($blog->countComments() > 0) {
						$blog_comments = $blog->getAnnotations('generic_comment');
						$blog_content .= <<<__HTML
<section class="post-comments">
<h1>Comments</h1>

__HTML;
						foreach ($blog_comments as $blog_comment) {
							$comment_author = get_entity($blog_comment->owner_guid)->name;
							$comment_pubdate = date('r', $blog_comment->time_created);
							$blog_content .= <<<__HTML
<article>
<header>
<h1 class="comment-date">By $comment_author on <time datetime="$comment_pubdate">$comment_pubdate</time></h1>
</header>
$blog_comment->value
</article>

__HTML;
						}
						$blog_content .= '<section>';
					}
					$blog_content .= <<<__HTML

</article>
</body>
</html>
__HTML;
					$zip->addFromString($blog_filedate.' '.$blog->title.'.html', $blog_content);
				}
			}
			$zip->close();
			$area .= '</ul>';
			$area .= '<br><p style="color: green;">ZIP file created successfully.</p><p>Download this <a href="'.$site_url.'file_takeout_download/'.$guid_from_path.'">ZIP file</a> to your computer and extract the contents to any folder.</p>';
		}
	} else {
		$area .= '<br><p style="color: red;">No files to export.</p>';
	}
	$area .= '<br><a href="'.$site_url.'file_takeout">&lt; Back to File Takeout</a>';
} 
// Display a listing of all groups that contain files
else {
	$area = '<br><p>This tool exports files from a group (which you own) into a ZIP archive.</p>';
	$all_groups = elgg_get_entities(array("type" => "group", "limit" => ""));
	$my_groups = 0;
	$sort_array = array();
	foreach ($all_groups as $group) {
		if (!isset($sort_array[$group->getOwnerEntity()->guid])) {
			$sort_array[$group->getOwnerEntity()->guid] = array();
		}
		$sort_array[$group->getOwnerEntity()->guid][] = $group;
	}
	foreach ($sort_array as $key => $val) {
		if ($key == $logged_in_user->guid || $logged_in_user->isAdmin() ) {
			$user = get_user($key);
			$area .= '<h3>Group Owner: ' . $user->name . '</h3>';
			$area .= '<ul>';
			foreach ($val as $group){
				$groupfiles = elgg_get_entities(array(
					'type' => 'object',
					'subtype' => 'file',
					'container_guid' => $group->guid,
					'limit' => '',
				));
				$blogs = elgg_get_entities(array(
					'type' => 'object',
					'subtype' => 'blog',
					'container_guid' => $group->guid,
					'limit' => '',
				));
				$area .= '<li>&gt; <a href="' . $group->getURL() . '">' . $group->name . '</a> (' . count($groupfiles) . ' files)(' . count($blogs) . ' blogs) -- <a href="' . $_SERVER['REQUEST_URI'] . '/' . $group->guid . '">Download Archive</a></li>';
				$my_groups++;
			}
			$area .= '</ul><br>';
		}
	}
	if ($my_groups == 0) {
			$area .= '<p><span style="color: red;">You do not own any groups.</span></p><br>';
	}
	$user_files = elgg_get_entities(array(
		'type' => 'object',
		'subtype' => 'file',
		'container_guid' => $logged_in_user->guid,
		'limit' => '',
	));
	$user_blogs = elgg_get_entities(array(
		'type' => 'object',
		'subtype' => 'blog',
		'container_guid' => $logged_in_user->guid,
		'limit' => '',
	));
	$area .= '<p>You may also download a ZIP of all your personal files (' . count($user_files) . ' files)(' . count($user_blogs) . ' blogs) --  <a href="' . $_SERVER['REQUEST_URI'] . '/' . $logged_in_user->guid . '">Download Archive</a></p>';
}

// Format page
$body = elgg_view_layout('one_column', array('content' => $title . $area));

// Draw it
echo elgg_view_page("File Takeout", $body);

?>