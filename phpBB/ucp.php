<?php 
/***************************************************************************
 *                                ucp.php
 *                            -------------------
 *   begin                : Saturday, Feb 13, 2001
 *   copyright            : (C) 2001 The phpBB Group
 *   email                : support@phpbb.com
 *
 *   $Id$
 *
 *
 ***************************************************************************/

/***************************************************************************
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation; either version 2 of the License, or
 *   (at your option) any later version.
 *
 ***************************************************************************/

// TODO for 2.2:
//
// * Registration
//    * Admin defineable use of COPPA
//    * Link to (additional?) registration conditions
//    * Form based click through rather than links
//    * Inform user of registration method i.e. if a valid email is required
//    * Admin defineable characters allowed in usernames?
//    * Admin forced revalidation of given user/s from ACP
//    * Simple registration (option or always?), i.e. username, email address, password
// * Tab based control panel
// * Modular/plug-in approach
// * Opening tab:
//    * Last visit time
//    * Last active in
//    * Most active in
//    * Current Karma
//    * New PM counter
//    * Unread PM counter
//    * Subscribed forum and topic lists + unsubscribe option, etc.
//    * (Unread?) Global announcements?
//    * Link/s to MCP if applicable?
// * Black and White lists
//    * Add buddy/ignored user
//    * Group buddies/ignored users?
//    * Mark posts/PM's of buddies different colour?
// * Preferences
//    * Username
//    * email address/es
//    * password
//    * Various flags
// * Profile
//    * As required
// * PM system
//    * See privmsg
// * Avatars
//    * as current but with definable width/height box?
// * Permissions?
//    * List permissions granted to this user (in UCP and ACP UCP)

define('IN_PHPBB', true);
$phpbb_root_path = './';
include($phpbb_root_path . 'extension.inc');
include($phpbb_root_path . 'common.'.$phpEx);

// Start session management
$user->start();
$user->setup();
$auth->acl($user->data);


// -----------------------
// Page specific functions
//
if (!empty($_REQUEST['mode']))
{
	$mode = $_REQUEST['mode'];

	switch ($mode)
	{
		case 'activate':
			include($phpbb_root_path . 'ucp/usercp_activate.'.$phpEx);
			break;

		case 'register':
			if ($user->data['user_id'] != ANONYMOUS)
			{
				redirect("index.$phpEx$SID");
			}
			include($phpbb_root_path . 'ucp/usercp_register.'.$phpEx);
			break;

		case 'login':
			if ($user->data['user_id'] != ANONYMOUS)
			{
				redirect("index.$phpEx$SID");
			}

			define('IN_LOGIN', true);
			login_box("ucp.$phpEx$SID&amp;mode=login");
			redirect("index.$phpEx$SID");
			break;

		case 'logout':
			if ($user->data['user_id'] != ANONYMOUS)
			{
				$user->destroy();
			}

			redirect("index.$phpEx$SID");
			break;

	}
}


// Some basic template vars
$template->assign_vars(array(
	'UCP_WELCOME_MSG'	=> $user->lang['UCP_WELCOME_MESSAGE'])
);


// Word censors $censors['match'] & $censors['replace']
$censors = array();
obtain_word_list($censors);


// "Home" module
$template->assign_block_vars('ucp_sections', array(
	'U_SECTION'	=> "ucp.$phpEx$SID",
	'SECTION'	=> $user->lang['UCP_Main'])
);

// Grab the other enabled UCP modules
$selected_module = (!empty($_REQUEST['module_id'])) ? $_REQUEST['module_id'] : '';
$sql = "SELECT module_id, module_name, module_filename 
	FROM " . UCP_MODULES_TABLE . " 
	ORDER BY module_order";
$result = $db->sql_query($sql);

while ($row = $db->sql_fetchrow($result))
{
	$template->assign_block_vars('ucp_sections', array(
		'U_SECTION'	=> "ucp.$phpEx$SID&amp;module_id=" . $row['module_id'],
		'SECTION'	=> $row['module_name'])
	);
	
	if ($row['module_id'] == $selected_module)
	{
		$module_to_include = $row['module_filename'] . '.' . $phpEx;
		include($phpbb_root_path . $module_to_include);
	}
}
$db->sql_freeresult($result);


// Subscribed Topics
$sql = "SELECT tw.topic_id, t.topic_title, t.topic_last_post_time, t.poll_start, t.topic_replies, t.topic_type, t.forum_id 
	FROM " . TOPICS_TABLE . " t, " . TOPICS_WATCH_TABLE . " tw
	WHERE t.topic_id = tw.topic_id 
		AND tw.user_id = " . $user->data['user_id'] . " 
	ORDER BY t.topic_last_post_time DESC";
$result = $db->sql_query($sql);

$topic_count = 0;
while ($row = $db->sql_fetchrow($result))
{
	$replies = $row['topic_replies']; 
	$topic_id = $row['topic_id'];
	$forum_id = $row['forum_id'];
	
	switch ($row['topic_type'])
	{
		case POST_ANNOUNCE:
			$topic_type = $user->lang['Topic_Announcement'] . ' ';
			$folder = 'folder_announce';
			$folder_new = 'folder_announce_new';
			break;

		case POST_STICKY:
			$topic_type = $user->lang['Topic_Sticky'] . ' ';
			$folder = 'folder_sticky';
			$folder_new = 'folder_sticky_new';
			break;

		case ITEM_LOCKED:
			$folder = 'folder_locked';
			$folder_new = 'folder_locked_new';
			break;

		default:
			if ($replies >= intval($config['hot_threshold']))
			{
				$folder = 'folder_hot';
				$folder_new = 'folder_hot_new';
			}
			else
			{
				$folder = 'folder';
				$folder_new = 'folder_new';
			}
			break;
	}

	$unread_topic = false;
	if ($user->data['user_id'] && $row['topic_last_post_time'] > $user->data['session_last_visit'])
	{
		$unread_topic = true;
	}

	$newest_post_img = ($unread_topic) ? '<a href="viewtopic.' . $phpEx . $SID . '&amp;t=' . $topic_id  . '&amp;view=newest#newest">' . $user->img('goto_post_newest', 'View_newest_post') . '</a> ' : '';
	$folder_img = ($unread_topic) ? $folder_new : $folder;
	$folder_alt = ($unread_topic) ? 'New_posts' : (($row['topic_status'] == ITEM_LOCKED) ? 'Topic_locked' : 'No_new_posts');

	$view_topic_url = 'viewtopic.' . $phpEx . $SID . '&amp;f=' . $forum_id . '&amp;t=' . $topic_id;

	// Needs to be handled within this code rather than going out of UCP
	$unsubscribe_img = '<a href="viewtopic.' . $phpEx . $SID . '&amp;t=' . $topic_id . '&amp;unwatch=topic">' . $user->img('icon_delete', 'Stop_watching_topic', FALSE) . '</a>';
	
	$template->assign_block_vars('subscribed_topics', array(
		'TOPIC_FOLDER_IMG'	=> $user->img($folder_img, $folder_alt),
		'NEWEST_POST_IMG'	=> $newest_post_img,
		'UNSUBSCRIBE_IMG'	=> $unsubscribe_img,	

		'TOPIC_TITLE'	=> (!empty($censors)) ? preg_replace($censors['match'], $censors['replace'], $row['topic_title']) : $row['topic_title'],
		
		'U_TOPIC'	=> $view_topic_url)
	);
}
$db->sql_freeresult($result);
// End Subscribed Topics


// Subscribed Forums
$sql = "SELECT f.forum_id, f.forum_last_post_time, f.forum_last_post_id, f.left_id, f.right_id, f.forum_status, f.forum_name, f.forum_desc 
	FROM " . FORUMS_TABLE . " f, " . FORUMS_WATCH_TABLE . " fw
	WHERE f.forum_id = fw.forum_id 
		AND fw.user_id = " . $user->data['user_id'] . " 
	ORDER BY f.forum_last_post_time DESC";
$result = $db->sql_query($sql);

while ($row = $db->sql_fetchrow($result))
{
	$forum_id = $row['forum_id'];

	$unread_topics = ($user->data['user_id'] && $row['forum_last_post_time'] > $user->data['user_lastvisit']) ? TRUE : FALSE;

	$folder_image = ($unread_topics) ? 'forum_new' : 'forum';
	$folder_alt = ($unread_topics) ? 'New_posts' : 'No_new_posts';

	if ($row['left_id'] + 1 < $row['right_id'])
	{
		$folder_image = ($unread_topics) ? 'sub_forum_new' : 'sub_forum';
		$folder_alt = ($unread_topics) ? 'New_posts' : 'No_new_posts';
	}
	elseif ($row['forum_status'] == ITEM_LOCKED)
	{
		$folder_image = 'forum_locked';
		$folder_alt = 'Forum_locked';
	}
	else
	{
		$folder_image = ($unread_topics) ? 'forum_new' : 'forum';
		$folder_alt = ($unread_topics) ? 'New_posts' : 'No_new_posts';
	}

	$last_post = '<a href="viewtopic.' . $phpEx . $SID . '&amp;f=' . $row['forum_id'] . '&amp;p=' . $row['forum_last_post_id'] . '#' . $row['forum_last_post_id'] . '">' . $user->img('goto_post_latest', 'View_latest_post') . '</a>';

	// Needs to be handled within this code rather than going out of UCP
	$unsubscribe_img = '<a href="viewforum.' . $phpEx . $SID . '&amp;f=' . $forum_id . '&amp;unwatch=forum">' . $user->img('icon_delete', 'Stop_watching_forum', FALSE) . '</a>';	
	
	$template->assign_block_vars('subscribed_forums', array(
		'FORUM_FOLDER_IMG'		=> $user->img($folder_image, $folder_alt),
		'NEWEST_FORUM_POST_IMG' => $last_post,
		'UNSUBSCRIBE_IMG'		=> $unsubscribe_img,

		'FORUM_NAME'	=> $row['forum_name'],
		
		'U_FORUM'	=> 'viewforum.' . $phpEx . $SID . '&amp;f=' . $row['forum_id'])
	);
}
$db->sql_freeresult($result);
// End Subscribed forums


// Buddy List

// End Buddy List


// Private Messages

// End Private Messages


// Output the page
page_header($user->lang['UCP'] . ' - ' . $this_section);

$template->set_filenames(array(
	'body' => 'usercp_main.html')
);

page_footer();

?>