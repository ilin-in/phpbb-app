<?php
/***************************************************************************
 *                                index.php
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
 *
 ***************************************************************************/
include('extension.inc');
include('common.'.$phpEx);

$pagetype = "index";
$page_title = "Forum Index";

//
// Start session management
//
$userdata = session_pagestart($user_ip, PAGE_INDEX, $session_length);
init_userprefs($userdata);
//
// End session management
//

$total_posts = get_db_stat('postcount');
$total_users = get_db_stat('usercount');
$newest_userdata = get_db_stat('newestuser');
$newest_user = $newest_userdata["username"];
$newest_uid = $newest_userdata["user_id"];
$users_browsing = get_db_stat("usersonline") . " Users ";

if(empty($viewcat))
{
	$viewcat = -1;
}

//
// Output page header and
// open the index body template
//
include('includes/page_header.'.$phpEx);

$template->set_filenames(array(
	"body" => "index_body.tpl"));

$template->assign_vars(array(
	"TOTAL_POSTS" => $total_posts,
	"TOTAL_USERS" => $total_users,
	"NEWEST_USER" => $newest_user,
	"NEWEST_UID" => $newest_uid,
	"USERS_BROWSING" => $users_browsing,

	"U_NEWEST_USER_PROFILE" => append_sid("profile.$phpEx?mode=viewprofile&".POST_USERS_URL."=$newest_uid"))
);

//
// Start main
//
$sql = "SELECT c.cat_id, c.cat_title, c.cat_order
	FROM ".CATEGORIES_TABLE." c, ".FORUMS_TABLE." f
	WHERE f.cat_id = c.cat_id
	GROUP BY c.cat_id, c.cat_title, c.cat_order
	ORDER BY c.cat_order";
if(!$q_categories = $db->sql_query($sql))
{
	error_die(SQL_QUERY, "Could not query categories list.", __LINE__, __FILE__);
}

$total_categories = $db->sql_numrows();

if($total_categories)
{
	$category_rows = $db->sql_fetchrowset($q_categories);

	$limit_forums = "";
	//
	// Define appropriate SQL 
	//
	switch(SQL_LAYER)
	{
		case 'postgresql':
			$limit_forums = ($viewcat != -1) ? "AND f.cat_id = $viewcat " : "";
			$sql = "SELECT f.*, t.topic_id, t.topic_replies, t.topic_last_post_id, p.post_time, u.username, u.user_id
				FROM ".FORUMS_TABLE." f, ".TOPICS_TABLE." t, ".POSTS_TABLE." p, ".USERS_TABLE." u
				WHERE f.forum_last_post_id = p.post_id
					AND p.post_id = t.topic_last_post_id 
					AND p.poster_id = u.user_id 
					$limit_forums
					UNION (
						SELECT f.*, NULL, NULL, NULL, NULL, NULL, NULL
						FROM ".FORUMS_TABLE." f
						WHERE NOT EXISTS (
							SELECT p.post_time 
							FROM ".POSTS_TABLE." p
							WHERE f.forum_last_post_id = p.post_id
						)
							$limit_forums
					)
				ORDER BY f.cat_id, f.forum_order";
			break;

		case 'oracle':
			$limit_forums = ($viewcat != -1) ? "AND f.cat_id = $viewcat " : "";
			$sql = "SELECT f.*, t.topic_id, t.topic_replies, t.topic_last_post_id, u.username, u.user_id, p.post_time
				FROM ".FORUMS_TABLE." f, ".POSTS_TABLE." p, ".TOPICS_TABLE." t, ".USERS_TABLE." u 
				WHERE f.forum_last_post_id = p.post_id(+) 
					AND p.post_id = t.topic_last_post_id(+) 
					AND p.poster_id = u.user_id(+) 
					$limit_forums
				ORDER BY f.cat_id, f.forum_order";
			break;

		default:
			// This works on: MySQL, MSSQL and ODBC (Access)
			$limit_forums = ($viewcat != -1) ? "WHERE f.cat_id = $viewcat " : "";
			$sql = "SELECT f.*, t.topic_id, t.topic_replies, t.topic_last_post_id, u.username, u.user_id, p.post_time, af.auth_view, af.auth_read, af.auth_post, af.auth_reply, af.auth_edit, af.auth_delete, af.auth_votecreate, af.auth_vote 
				FROM ((( ".FORUMS_TABLE." f
				LEFT JOIN ".POSTS_TABLE." p ON f.forum_last_post_id = p.post_id )
				LEFT JOIN ".TOPICS_TABLE." t ON p.post_id = t.topic_last_post_id )
				LEFT JOIN ".USERS_TABLE." u ON p.poster_id = u.user_id )
				LEFT JOIN ".AUTH_FORUMS_TABLE." af ON af.forum_id = f.forum_id 
				$limit_forums
				ORDER BY f.cat_id, f.forum_order";
			break;
	}
	if(!$q_forums = $db->sql_query($sql))
	{
		error_die(SQL_QUERY, "Could not query forums information.", __LINE__, __FILE__);
	}
	$total_forums = $db->sql_numrows($q_forums);
	$forum_rows = $db->sql_fetchrowset($q_forums);

	//
	// Note that this doesn't resolve conflicts where a user
	// is banned/disallowed mod right from a group but that
	// group has moderation rights ... but then hopefully
	// this sort of stuff can be resolved in the admin
	// section ... or at least brought to the attention
	// of the board admin, after that it's really their
	// business (besides when it comes to 'actual' moderating
	// a more precise auth() check is done anyway ...)
	//
	$sql = "SELECT f.forum_id, u.username, u.user_id   
		FROM ".FORUMS_TABLE." f, ".USERS_TABLE." u, ".USER_GROUP_TABLE." ug, ".AUTH_ACCESS_TABLE." aa 
		WHERE aa.forum_id = f.forum_id 
			AND aa.auth_mod = 1 
			AND ug.group_id = aa.group_id 
			AND u.user_id = ug.user_id 
		ORDER BY f.forum_id, u.user_id";
	if(!$q_forum_mods = $db->sql_query($sql))
	{
		error_die(SQL_QUERY, "Could not query forum moderator information.", __LINE__, __FILE__);
	}
	$forum_mods_list = $db->sql_fetchrowset($q_forum_mods);

	for($i = 0; $i < count($forum_mods_list); $i++)
	{
		$forum_mods['forum_'.$forum_mods_list[$i]['forum_id'].'_name'][] = $forum_mods_list[$i]['username'];
		$forum_mods['forum_'.$forum_mods_list[$i]['forum_id'].'_id'][] = $forum_mods_list[$i]['user_id'];
	}

	//
	// Find which forums are visible for
	// this user
	//
	$is_auth_ary = auth(AUTH_VIEW, AUTH_LIST_ALL, $userdata, $forum_rows);

	//
	// Okay, let's build the index
	//
	$gen_cat = array();

	for($i = 0; $i < $total_categories; $i++)
	{
		for($j = 0; $j < $total_forums; $j++)
		{
			if( ( ($forum_rows[$j]['cat_id'] == $category_rows[$i]['cat_id'] && $viewcat == -1) ||
				($category_rows[$i]['cat_id'] == $viewcat) ) && 
				$is_auth_ary[$forum_rows[$j]['forum_id']]['auth_view'])
			{
				$folder_image = "<img src=\"".$images['folder']."\">";
				$posts = $forum_rows[$j]['forum_posts'];
				$topics = $forum_rows[$j]['forum_topics'];
				if($forum_rows[$j]['username'] != "" && $forum_rows[$j]['post_time'] > 0)
				{
					$last_post_time = create_date($board_config['default_dateformat'], $forum_rows[$j]['post_time'], $board_config['default_timezone']);

					$last_post = $last_post_time."<br>by ";
					$last_post .= "<a href=\"".append_sid("profile.$phpEx?mode=viewprofile&".POST_USERS_URL."=".$forum_rows[$j]['user_id']) ."\">".$forum_rows[$j]['username']."</a>&nbsp;";

					$last_post .= "<a href=\"".append_sid("viewtopic.".$phpEx."?".POST_POST_URL."=".$forum_rows[$j]['topic_last_post_id']) . "#" . $forum_rows[$j]['topic_last_post_id']."\"><img src=\"".$images['latest_reply']."\" width=\"20\" height=\"11\" border=\"0\" alt=\"View Latest Post\"></a>";
				}
				else
				{
					$last_post = "No Posts";
					$forum_rows[$j]['forum_name'] = stripslashes($forum_rows[$j]['forum_name']);
				}

				if($row_color == "#DDDDDD")
				{
					$row_color = "#CCCCCC";
				}
				else
				{
					$row_color = "#DDDDDD";
				}

				unset($moderators_links);
				for($mods = 0; $mods < count($forum_mods['forum_'.$forum_rows[$j]['forum_id'].'_id']); $mods++)
				{
					if(isset($moderators_links))
					{
						$moderators_links .= ", ";
					}
					if(!($mods % 2) && $mods != 0)
					{
						$moderators_links .= "<br>";
					}
					$moderators_links .= "<a href=\"".append_sid("profile.$phpEx?mode=viewprofile&".POST_USERS_URL."=".$forum_mods['forum_'.$forum_rows[$j]['forum_id'].'_id'][$mods])."\">".$forum_mods['forum_'.$forum_rows[$j]['forum_id'].'_name'][$mods]."</a>";
				}

				if(!$gen_cat[$category_rows[$i]['cat_id']])
				{
					$category_rows[$i]['cat_id']. " : " . $gen_cat[$category_rows[$i]['cat_id']]."<br>";
					$template->assign_block_vars("catrow", array(
						"CAT_ID" => $category_rows[$i]['cat_id'],
						"CAT_DESC" => stripslashes($category_rows[$i]['cat_title']),
						"U_VIEWCAT" => append_sid("index." . $phpEx . "?viewcat=" . $category_rows[$i]['cat_id']))
					);
					$gen_cat[$category_rows[$i]['cat_id']] = 1;
				}

				$template->assign_block_vars("catrow.forumrow", 
					array(
						"FOLDER" => $folder_image,
						"FORUM_NAME" => stripslashes($forum_rows[$j]['forum_name']),
						"FORUM_DESC" => stripslashes($forum_rows[$j]['forum_desc']),
						"ROW_COLOR" => $row_color,
						"POSTS" => $forum_rows[$j]['forum_posts'],
						"TOPICS" => $forum_rows[$j]['forum_topics'],
						"LAST_POST" => $last_post,
						"MODERATORS" => $moderators_links,

						"U_VIEWFORUM" => append_sid("viewforum." . $phpEx . "?" . POST_FORUM_URL . "=" . $forum_rows[$j]['forum_id'] . "&" . $forum_rows[$j]['forum_posts']))
				);
			}
			else if($viewcat != -1)
			{
				if(!$gen_cat[$category_rows[$i]['cat_id']])
				{
					$template->assign_block_vars("catrow", array(
						"CAT_ID" => $category_rows[$i]['cat_id'],
						"CAT_DESC" => stripslashes($category_rows[$i]['cat_title']),
						"U_VIEWCAT" => append_sid("index." . $phpEx . "?viewcat=" . $category_rows[$i]['cat_id']))
					);
					$gen_cat[$category_rows[$i]['cat_id']] = 1;
				}
			}
		}
	} // for ... categories

}// if ... total_categories
else
{
   error_die(GENERAL_ERROR, "There are no Categories or Forums on this board.");
}
$template->pparse("body");

include('includes/page_tail.'.$phpEx);
?>