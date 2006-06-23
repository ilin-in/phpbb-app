<?php
/** 
*
* @package mcp
* @version $Id$
* @copyright (c) 2005 phpBB Group 
* @license http://opensource.org/licenses/gpl-license.php GNU Public License 
*
*/

/**
* MCP Front Panel
*/
function mcp_front_view($id, $mode, $action)
{
	global $phpEx, $phpbb_root_path, $config;
	global $template, $db, $user, $auth;

	// Latest 5 unapproved
	$forum_list = get_forum_list('m_approve');
	$post_list = array();
	$forum_names = array();

	$forum_id = request_var('f', 0);

	$template->assign_var('S_SHOW_UNAPPROVED', (!empty($forum_list)) ? true : false);
	
	if (!empty($forum_list))
	{
		$sql = 'SELECT COUNT(post_id) AS total
			FROM ' . POSTS_TABLE . '
			WHERE forum_id IN (0, ' . implode(', ', $forum_list) . ')
				AND post_approved = 0';
		$result = $db->sql_query($sql);
		$total = (int) $db->sql_fetchfield('total');
		$db->sql_freeresult($result);

		if ($total)
		{
			$global_id = $forum_list[0];

			$sql = 'SELECT forum_id, forum_name
				FROM ' . FORUMS_TABLE . '
				WHERE forum_id IN (' . implode(', ', $forum_list) . ')';
			$result = $db->sql_query($sql);

			while ($row = $db->sql_fetchrow($result))
			{
				$forum_names[$row['forum_id']] = $row['forum_name'];
			}
			$db->sql_freeresult($result);

			$sql = 'SELECT post_id
				FROM ' . POSTS_TABLE . '
				WHERE forum_id IN (0, ' . implode(', ', $forum_list) . ')
					AND post_approved = 0
				ORDER BY post_time DESC';
			$result = $db->sql_query_limit($sql, 5);

			while ($row = $db->sql_fetchrow($result))
			{
				$post_list[] = $row['post_id'];
			}
			$db->sql_freeresult($result);

			$sql = 'SELECT p.post_id, p.post_subject, p.post_time, p.poster_id, p.post_username, u.username, t.topic_id, t.topic_title, t.topic_first_post_id, p.forum_id
				FROM ' . POSTS_TABLE . ' p, ' . TOPICS_TABLE . ' t,  ' . USERS_TABLE . ' u
				WHERE p.post_id IN (' . implode(', ', $post_list) . ')
					AND t.topic_id = p.topic_id
					AND p.poster_id = u.user_id
				ORDER BY p.post_time DESC';
			$result = $db->sql_query($sql);

			while ($row = $db->sql_fetchrow($result))
			{
				$global_topic = ($row['forum_id']) ? false : true;
				if ($global_topic)
				{
					$row['forum_id'] = $global_id;
				}

				$template->assign_block_vars('unapproved', array(
					'U_POST_DETAILS'	=> append_sid("{$phpbb_root_path}mcp.$phpEx", 'i=main&amp;mode=post_details&amp;f=' . $row['forum_id'] . '&amp;p=' . $row['post_id']),
					'U_MCP_FORUM'		=> (!$global_topic) ? append_sid("{$phpbb_root_path}mcp.$phpEx", 'i=main&amp;mode=forum_view&amp;f=' . $row['forum_id']) : '',
					'U_MCP_TOPIC'		=> append_sid("{$phpbb_root_path}mcp.$phpEx", 'i=main&amp;mode=topic_view&amp;f=' . $row['forum_id'] . '&amp;t=' . $row['topic_id']),
					'U_FORUM'			=> (!$global_topic) ? append_sid("{$phpbb_root_path}viewforum.$phpEx", 'f=' . $row['forum_id']) : '',
					'U_TOPIC'			=> append_sid("{$phpbb_root_path}viewtopic.$phpEx", 'f=' . $row['forum_id'] . '&amp;t=' . $row['topic_id']),
					'U_AUTHOR'			=> ($row['poster_id'] == ANONYMOUS) ? '' : append_sid("{$phpbb_root_path}memberlist.$phpEx", 'mode=viewprofile&amp;u=' . $row['poster_id']),

					'FORUM_NAME'	=> (!$global_topic) ? $forum_names[$row['forum_id']] : $user->lang['GLOBAL_ANNOUNCEMENT'],
					'TOPIC_TITLE'	=> $row['topic_title'],
					'AUTHOR'		=> ($row['poster_id'] == ANONYMOUS) ? (($row['post_username']) ? $row['post_username'] : $user->lang['GUEST']) : $row['username'],
					'SUBJECT'		=> ($row['post_subject']) ? $row['post_subject'] : $user->lang['NO_SUBJECT'],
					'POST_TIME'		=> $user->format_date($row['post_time']))
				);
			}
			$db->sql_freeresult($result);
		}

		if ($total == 0)
		{
			$template->assign_vars(array(
				'L_UNAPPROVED_TOTAL'		=> $user->lang['UNAPPROVED_POSTS_ZERO_TOTAL'],
				'S_HAS_UNAPPROVED_POSTS'	=> false)
			);
		}
		else
		{
			$template->assign_vars(array(
				'L_UNAPPROVED_TOTAL'		=> ($total == 1) ? $user->lang['UNAPPROVED_POST_TOTAL'] : sprintf($user->lang['UNAPPROVED_POSTS_TOTAL'], $total),
				'S_HAS_UNAPPROVED_POSTS'	=> true)
			);
		}
	}

	// Latest 5 reported
	$forum_list = get_forum_list('m_');

	$template->assign_var('S_SHOW_REPORTS', (!empty($forum_list)) ? true : false);

	if (!empty($forum_list))
	{
		$sql = 'SELECT COUNT(r.report_id) AS total
			FROM ' . REPORTS_TABLE . ' r, ' . POSTS_TABLE . ' p
			WHERE r.post_id = p.post_id
				AND r.report_closed = 0
				AND p.forum_id IN (0, ' . implode(', ', $forum_list) . ')';
		$result = $db->sql_query($sql);
		$total = (int) $db->sql_fetchfield('total');
		$db->sql_freeresult($result);

		if ($total)
		{
			$global_id = $forum_list[0];

			$sql = $db->sql_build_query('SELECT', array(
				'SELECT'	=> 'r.*, p.post_id, p.post_subject, u.username, t.topic_id, t.topic_title, f.forum_id, f.forum_name',

				'FROM'		=> array(
					REPORTS_TABLE			=> 'r',
					REPORTS_REASONS_TABLE	=> 'rr',
					TOPICS_TABLE			=> 't',
					USERS_TABLE				=> 'u',
					POSTS_TABLE				=> 'p'
				),

				'LEFT_JOIN'	=> array(
					array(
						'FROM'	=> array(FORUMS_TABLE => 'f'),
						'ON'	=> 'f.forum_id = p.forum_id'
					)
				),

				'WHERE'		=> 'r.post_id = p.post_id
					AND r.report_closed = 0
					AND r.reason_id = rr.reason_id
					AND p.topic_id = t.topic_id
					AND r.user_id = u.user_id
					AND p.forum_id IN (0, ' . implode(', ', $forum_list) . ')',

				'ORDER_BY'	=> 'p.post_time DESC'
			));
			$result = $db->sql_query_limit($sql, 5);

			while ($row = $db->sql_fetchrow($result))
			{
				$global_topic = ($row['forum_id']) ? false : true;
				if ($global_topic)
				{
					$row['forum_id'] = $global_id;
				}

				$template->assign_block_vars('report', array(
					'U_POST_DETAILS'	=> append_sid("{$phpbb_root_path}mcp.$phpEx", 'f=' . $row['forum_id'] . '&amp;p=' . $row['post_id'] . "&amp;i=reports&amp;mode=report_details"),
					'U_MCP_FORUM'		=> (!$global_topic) ? append_sid("{$phpbb_root_path}mcp.$phpEx", 'f=' . $row['forum_id'] . "&amp;i=$id&amp;mode=forum_view") : '',
					'U_MCP_TOPIC'		=> append_sid("{$phpbb_root_path}mcp.$phpEx", 'f=' . $row['forum_id'] . '&amp;t=' . $row['topic_id'] . "&amp;i=$id&amp;mode=topic_view"),
					'U_FORUM'			=> (!$global_topic) ? append_sid("{$phpbb_root_path}viewforum.$phpEx", 'f=' . $row['forum_id']) : '',
					'U_TOPIC'			=> append_sid("{$phpbb_root_path}viewtopic.$phpEx", 'f=' . $row['forum_id'] . '&amp;t=' . $row['topic_id']),
					'U_REPORTER'		=> ($row['user_id'] == ANONYMOUS) ? '' : append_sid("{$phpbb_root_path}memberlist.$phpEx", 'mode=viewprofile&amp;u=' . $row['user_id']),

					'FORUM_NAME'	=> (!$global_topic) ? $row['forum_name'] : $user->lang['GLOBAL_ANNOUNCEMENT'],
					'TOPIC_TITLE'	=> $row['topic_title'],
					'REPORTER'		=> ($row['user_id'] == ANONYMOUS) ? $user->lang['GUEST'] : $row['username'],
					'SUBJECT'		=> ($row['post_subject']) ? $row['post_subject'] : $user->lang['NO_SUBJECT'],
					'REPORT_TIME'	=> $user->format_date($row['report_time']))
				);
			}
		}

		if ($total == 0)
		{
			$template->assign_vars(array(
				'L_REPORTS_TOTAL'	=>	$user->lang['REPORTS_ZERO_TOTAL'],
				'S_HAS_REPORTS'		=>	false)
			);
		}
		else
		{
			$template->assign_vars(array(
				'L_REPORTS_TOTAL'	=> ($total == 1) ? $user->lang['REPORT_TOTAL'] : sprintf($user->lang['REPORTS_TOTAL'], $total),
				'S_HAS_REPORTS'		=> true)
			);
		}
	}

	// Latest 5 logs
	$forum_list = get_forum_list(array('m_', 'a_'));

	if (!empty($forum_list))
	{
		// Add forum_id 0 for global announcements
		$forum_list[] = 0;

		$log_count = 0;
		$log = array();
		view_log('mod', $log, $log_count, 5, 0, $forum_list);

		foreach ($log as $row)
		{
			$template->assign_block_vars('log', array(
				'USERNAME'		=> $row['username'],
				'IP'			=> $row['ip'],
				'TIME'			=> $user->format_date($row['time']),
				'ACTION'		=> $row['action'],
				'U_VIEWTOPIC'	=> (!empty($row['viewtopic'])) ? $row['viewtopic'] : '',
				'U_VIEWLOGS'	=> (!empty($row['viewlogs'])) ? $row['viewlogs'] : '')
			);
		}
	}

	$template->assign_vars(array(
		'S_SHOW_LOGS'	=> (!empty($forum_list)) ? true : false,
		'S_HAS_LOGS'	=> (!empty($log)) ? true : false)
	);

	$template->assign_var('S_MCP_ACTION', append_sid("{$phpbb_root_path}mcp.$phpEx"));
	make_jumpbox(append_sid("{$phpbb_root_path}mcp.$phpEx", 'i=main&amp;mode=forum_view'), 0, false, 'm_');
}

?>