<?php
namespace andreask\ium\classes;


class top_topics
{

	/**
	 *
	 * This file is part of the phpBB Forum Software package.
	 *
	 * @copyright (c) phpBB Limited <https://www.phpbb.com>
	 * @license GNU General Public License, version 2 (GPL-2.0)
	 *
	 * For full copyright and license information, please see
	 * the docs/CREDITS.txt file.
	 *
	 */


	protected $user_id = null;
	protected $db;
	protected $config;
	protected $auth;

	public function __construct(\phpbb\config\config $config, \phpbb\auth\auth $auth, \phpbb\db\driver\driver_interface $db)
	{
		$this->config 	= $config;
		$this->auth 	= $auth;
		$this->db		= $db;
	}

	public function set_id($id){
		$this->user_id=$id;
	}


	public function get_user_top_topics($id)
	{
		if (!$id)
		{
			return false;
		}
		$this->set_id($id);

		if($this->user_post_count($this->user_id) > $this->config['andreask_ium_top_user_threads_count'])
		{
			// Obtain most active topic for user
			$sql = 'SELECT forum_id, topic_id, count(post_id) as posts_count
			FROM ' . POSTS_TABLE . '
			WHERE poster_id = ' . $this->user_id . '
			AND post_postcount = 1
			GROUP BY forum_id, topic_id 
			ORDER BY posts_count DESC';

			$result = $this->db->sql_query_limit($sql, $this->config['andreask_ium_top_user_threads_count']);
			$active_t_row = array();

			while ($row = $this->db->sql_fetchrow($result)) {
				$active_t_row[] = $row;
			};

			$this->db->sql_freeresult($result);

			if (!empty($active_t_row))
			{
				foreach($active_t_row as &$post)
				{
					$sql = 'SELECT topic_title as title
							FROM ' . TOPICS_TABLE . '
							WHERE topic_id = ' . $post['topic_id'];

					$result = $this->db->sql_query($sql);
					$post['topic_title'] = (string) $this->db->sql_fetchfield('title');
					$this->db->sql_freeresult($result);
					$post['have_access'] = (bool) $this->user_access($post['forum_id']);
				}
				echo "<pre>";
				var_export($active_t_row);
				echo "</pre>";
				return $active_t_row;
			}
			return false;
		}
	}

	/**
	 * Give the amount of post for user
	 * @param $user user_id
	 * @return bool|int Number of posts or else return false
	 */

	private function user_post_count($user_id)
	{
		if ($user_id)
		{
			$sql = 'SELECT user_posts AS post_count 
				FROM ' . USERS_TABLE . ' 
				WHERE user_id = ' . $user_id;

			$result = $this->db->sql_query($sql);
			$post_count = (int) $this->db->sql_fetchfield('post_count');
			$this->db->sql_freeresult($result);
			return $post_count;
		}
		return false;
	}

	public function user_access($forum_id){
		$data = $this->auth->obtain_user_data($this->user_id);
		$this->auth->acl($data);
		return  $this->auth->acl_get('f_read', $forum_id);
	}

}