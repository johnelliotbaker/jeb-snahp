<?php

/**
 * undocumented function
 *
 * @return void
 */
namespace jeb\snahp\core;

use phpbb\template\context;
use phpbb\user;
use phpbb\auth\auth;
use phpbb\request\request_interface;
use phpbb\db\driver\driver_interface;
use phpbb\config\config;
use phpbb\controller\helper;
use phpbb\language\language;
use phpbb\template\template;
use phpbb\notification\manager;

function prn($var, $b_html=false) {
    if (is_array($var))
    { foreach ($var as $k => $v) { echo "... $k => "; prn($v, $b_html); }
    } else {
        if ($b_html)
        {
            echo htmlspecialchars($var) . '<br>';
        }
        else
        {
            echo $var . '<br>';
        }
    }
}


abstract class base
{
    protected $template_context;
    protected $container;
    protected $user;
    protected $auth;
    protected $request;
    protected $db;
    protected $config;
    protected $helper;
    protected $language;
    protected $template;
    protected $table_prefix;
    protected $notification;
	protected $u_action;
    protected $allowed_directive = ['table', 'tr', 'td', 'a', 'img', 'span'];

    public function set_template_context(context $ctx)
    {
        $this->template_context = $ctx;
    }
    public function set_container($container)
    {
        $this->container = $container;
    }
    public function set_user(user $user)
    {
        $this->user = $user;
    }
    public function set_auth(auth $auth)
    {
        $this->auth = $auth;
    }
    public function set_request(request_interface $request)
    {
        $this->request = $request;
    }
    public function set_db($db)
    {
        $this->db = $db;
    }
    public function set_config(config $config)
    {
        $this->config = $config;
    }
    public function set_helper(helper $helper)
    {
        $this->helper = $helper;
    }
    public function set_language(language $language)
    {
        $this->language = $language;
    }
    public function set_template(template $template)
    {
        $this->template = $template;
    }
    public function set_table_prefix($table_prefix)
    {
        $this->table_prefix = $table_prefix;
        // $this->table_prefix = 'phpbb_';
    }
    public function set_notification(manager $manager)
    {
        $this->notification = $manager;
    }
    
    // SESSION MANAGEMENT
    public function set_cookie($key, $data)
    {
        $this->user->set_cookie($key, tracking_serialize($data), 0, 0);
        // set_cookie($key, $data, 365, false);
        // $last_visit = $this->user->data['user_lastvisit'];
        // $this->user->set_cookie($key, tracking_serialize($data), $last_visit + 31536000);
        // $this->request->overwrite($this->config['cookie_name'] . '_' . $key, tracking_serialize($data), \phpbb\request\request_interface::COOKIE);
    }

    public function get_or_set_cookie($key, $data=[], $b_return_first=false)
    {
        $cookie = $this->get_cookie($key);
        // If cookie exists
        if ($cookie)
        {
            if ($b_return_first)
            {
                return $cookie[0];
            }
            return $cookie;

        }
        // If cookie doesn't exist
        $this->set_cookie($key, $data);
        if ($b_return_first)
        {
            return $data[0];
        }
        return $data;
        // $this->user->set_cookie($key, tracking_serialize($data), 0, 0);
        // set_cookie($key, $data, 365, false);
        // $last_visit = $this->user->data['user_lastvisit'];
        // $this->user->set_cookie($key, tracking_serialize($data), $last_visit + 31536000);
        // $this->request->overwrite($this->config['cookie_name'] . '_' . $key, tracking_serialize($data), \phpbb\request\request_interface::COOKIE);
    }

    public function get_cookie($key)
    {
        $cookie = $this->request->variable($this->config['cookie_name'] . '_' . $key, '', true, \phpbb\request\request_interface::COOKIE);
        $cookie = ($cookie) ? tracking_unserialize($cookie) : [];
        return $cookie;
    }

    // DATABASE Functions

    // Custom Templates
    public function upsert_tpl($user_id, $name, $text)
    {
        $tbl = $this->container->getParameter('jeb.snahp.tables');
        $user_id = (int) $user_id;
        if (!$user_id || !$name || !$text)
        {
            return false;
        }
        $data = [
            'user_id' => $user_id,
            'name' => $name,
            'text' => $text,
        ];
        $sql = 'INSERT INTO ' . $tbl['tpl'] . $this->db->sql_build_array('INSERT', $data) . "
            ON DUPLICATE KEY UPDATE text='{$text}'";
        $this->db->sql_query($sql);
        return $sql;
    }

    public function update_tpl($user_id, $name, $text)
    {
        $tbl = $this->container->getParameter('jeb.snahp.tables');
        $user_id = (int) $user_id;
        if (!$user_id || !$name || !$text)
        {
            return false;
        }
        $data['text'] = $text;
        $sql = 'UPDATE ' . $tbl['tpl'] . '
            SET ' . $this->db->sql_build_array('UPDATE', $data) . "
            WHERE user_id={$user_id} AND name='{$name}'";
        $this->db->sql_query($sql);
        return $sql;
    }

    public function delete_tpl($user_id, $name)
    {
        $tbl = $this->container->getParameter('jeb.snahp.tables');
        if (!$user_id || !$name)
        {
            return false;
        }
        $sql = 'DELETE FROM '  . $tbl['tpl'] . "
            WHERE user_id={$user_id} AND name='{$name}'";
        $this->db->sql_query($sql);
        return true;
    }

    public function select_tpl($user_id, $b_full=true, $name='')
    {
        $tbl = $this->container->getParameter('jeb.snahp.tables');
        $s_fields = $b_full ? '*' : 'id, name';
        $sql = "SELECT {$s_fields} FROM " . $tbl['tpl'] . ' WHERE user_id=' . (int)$user_id;
        if ($name)
        {
            $sql .= " AND name='{$name}'";
        }
        $sql .= " ORDER BY id DESC";
        $result = $this->db->sql_query($sql);
        $rowset = $this->db->sql_fetchrowset($result);
        $this->db->sql_freeresult($result);
        return $rowset;
    }

    // Invites
    public function select_invitee($invitee_id)
    {
        $tbl = $this->container->getParameter('jeb.snahp.tables');
        $sql = 'SELECT * FROM ' . $tbl['invite'] . ' WHERE redeemer_id=' . (int)$invitee_id;
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        return $row;
    }

    // THANKS
    public function select_thanks_for_op($topic_id, $cachetime=0)
    {
        $sql = 'SELECT COUNT(*) as count FROM phpbb_thanks where topic_id=' . $topic_id;
        $result = $this->db->sql_query($sql, $cachetime);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        if (!$row)
        {
            return 0;
        }
        return (int) $row['count'];
    }

    public function delete_thanks_notifications()
    {
        $sql = 'SELECT * FROM ' . 
            NOTIFICATION_TYPES_TABLE . '
            WHERE notification_type_name in ("gfksx.thanksforposts.notification.type.thanks", "gfksx.thanksforposts.notification.type.thanks_remove")';
        $result = $this->db->sql_query($sql);
        $rowset = $this->db->sql_fetchrowset($result);
        $this->db->sql_freeresult($result);
        $type_ids = array_map(function($arg){return $arg['notification_type_id'];}, $rowset);
        $sql = 'DELETE FROM ' . NOTIFICATIONS_TABLE . '
            WHERE user_id=' . $this->user->data['user_id'] . '
        AND ' . $this->db->sql_in_set('notification_type_id', $type_ids);
        $this->db->sql_query($sql);
    }

    // BUMP TOPIC
    public function get_bump_permission($tid)
    {
        // Init
        $user_id = $this->user->data['user_id'];
        $group_id = $this->user->data['group_id'];
        $def = $this->container->getParameter('jeb.snahp.bump_topic')['def'];
        // database
        $topic_data = $this->select_topic($tid);
        $bump_data = $this->select_bump_topic($tid);
        $group_config = $this->select_bump_group_config($group_id);
        // setup
        $b_mod = $this->is_mod();
        $status = $bump_data ? $bump_data['status'] : null;
        $b_op = $topic_data && $topic_data['topic_poster']==$user_id;
        $b_group_enabled = $group_config['snp_enable_bump'];
        // Conditions
        $b_disable = ($b_mod && $status==$def['enable']);
        $b_enable = $b_mod && (!$bump_data || $status==$def['disable']);
        $b_enable |= ($b_op && $b_group_enabled && !$bump_data);
        $b_bump = ($b_mod || $b_op) && ($bump_data && $status==$def['enable']);
        $data = [
            'b_enable'  => (bool)$b_enable,
            'b_disable' => (bool)$b_disable,
            'b_bump'    => (bool)$b_bump,
            'bump_data' => $bump_data,
            'topic_data' => $topic_data,
        ];
        return $data;
    }

    public function select_bump_group_config($group_id)
    {
        $sql = 'SELECT group_id, snp_bump_cooldown, snp_enable_bump FROM ' . GROUPS_TABLE .'
            WHERE group_id=' . $group_id;
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        return $row;
    }

    public function select_bump_topic($tid)
    {
        $tbl = $this->container->getParameter('jeb.snahp.tables');
        $sql = 'SELECT * FROM ' . $tbl['bump_topic'] . '
            WHERE tid=' . $tid;
        $result = $this->db->sql_query_limit($sql, 1);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        return $row;
    }

    public function update_bump_topic($tid, $data)
    {
        $tbl = $this->container->getParameter('jeb.snahp.tables');
        $sql = 'UPDATE ' . $tbl['bump_topic'] . '
            SET ' . $this->db->sql_build_array('UPDATE', $data) . '
            WHERE tid=' . $tid;
        $this->db->sql_query($sql);
    }

    public function delete_bump_topic($topic_data)
    {
        $tbl = $this->container->getParameter('jeb.snahp.tables');
        $tid = $topic_data['topic_id'];
        $sql = 'DELETE FROM ' . $tbl['bump_topic'] .'
            WHERE tid='. $tid;
        $this->db->sql_query($sql);
    }

    public function create_bump_topic($topic_data)
    {
        $tbl = $this->container->getParameter('jeb.snahp.tables');
        $tid = $topic_data['topic_id'];
        $tt = $topic_data['topic_time'];
        $tlpt = $topic_data['topic_last_post_time'];
        $topic_poster = $topic_data['topic_poster'];
        $data = [
            'tid'           => $tid,
            'moderator_uid' => $this->user->data['user_id'],
            'poster_uid'    => $topic_poster,
            'topic_time'    => $tt,
            'prev_topic_time'           => $tt,
            'prev_topic_last_post_time' => $tlpt,

        ];
        $sql = 'INSERT INTO ' . $tbl['bump_topic'] .
            $this->db->sql_build_array('INSERT', $data);
        $this->db->sql_query($sql);
    }

    // GET STYLE INFORMATION
    public function select_style_name()
    {
        $user_style = $this->user->data['user_style'];
        $sql = 'SELECT style_name FROM ' . $this->table_prefix . 'styles
            WHERE style_id=' . $user_style;
        $result = $this->db->sql_query_limit($sql, 1);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        $style_name = $row['style_name'];
        return $style_name;
    }

    // ALL SUBFORUM ID
    public function select_subforum($parent_id, $cooldown=0)
    {
        $sql = 'SELECT left_id, right_id FROM ' . FORUMS_TABLE . ' WHERE forum_id=' . $parent_id;
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        $parent_left_id = $row['left_id'];
        $parent_right_id = $row['right_id'];
        $sql = 'SELECT forum_id FROM ' . FORUMS_TABLE . ' WHERE parent_id = ' . $parent_id . ' OR (left_id BETWEEN ' . $parent_left_id . ' AND ' . $parent_right_id . ')';
        $result = $this->db->sql_query($sql, $cooldown);
        $data = array_map(function($array){return $array['forum_id'];}, $this->db->sql_fetchrowset($result));
        $this->db->sql_freeresult($result);
        return $data;
    }

    // FAVORITE CONTENTS
    public function select_accepted_requests($per_page, $start, $status_type='all', $user_id=null)
    {
        $maxi_query = 300;
        if ($user_id===null)
        {
            $user_id = $this->user->data['user_id'];
        }
        switch($status_type)
        {
        case 'dib':
            $def = $this->container->getParameter('jeb.snahp.req')['def'];
            $def_dib = $def['dib'];
            prn($def_dib);
            $status_condition = " r.status={$def_dib} ";
            break;
        case 'all':
        default:
            $status_condition = ' 1= 1 ';
        }
        $tbl = $this->container->getParameter('jeb.snahp.tables');
        $sql = 'SELECT 
            r.tid, r.fid, r.pid, r.created_time,
            r.requester_uid, r.status,
            t.topic_title
            FROM
                ('. $tbl['req'] . ' r)
            LEFT JOIN ('. TOPICS_TABLE ." t) 
            ON (t.topic_id=r.tid)
            WHERE
                r.fulfiller_uid={$user_id} AND {$status_condition} ORDER BY r.created_time DESC";
        $result = $this->db->sql_query_limit($sql, $maxi_query);
        $rowset = $this->db->sql_fetchrowset($result);
        $total = count($rowset);
        $this->db->sql_freeresult($result);
        $result = $this->db->sql_query_limit($sql, $per_page, $start);
        $rowset = $this->db->sql_fetchrowset($result);
        $this->db->sql_freeresult($result);
        return [$rowset, $total];
    }

    public function select_fulfilled_requests($per_page, $start, $user_id=null)
    {
        $maxi_query = 300;
        if ($user_id===null)
        {
            $user_id = $this->user->data['user_id'];
        }
        $tbl = $this->container->getParameter('jeb.snahp.tables');
        $def = $this->container->getParameter('jeb.snahp.req')['def'];
        $def_fulfill = $def['fulfill'];
        $sql = 'SELECT 
            r.tid, r.fid, r.pid, r.created_time,
            r.requester_uid, r.status,
            t.topic_title
            FROM
                ('. $tbl['req'] . ' r)
            LEFT JOIN ('. TOPICS_TABLE .' t) 
            ON (t.topic_id=r.tid)
            WHERE
                r.requester_uid=' . $user_id . '
            AND ' . $this->db->sql_in_set('status', $def_fulfill) .'
            ORDER BY r.created_time DESC';
        $result = $this->db->sql_query_limit($sql, $maxi_query);
        $rowset = $this->db->sql_fetchrowset($result);
        $total = count($rowset);
        $this->db->sql_freeresult($result);
        $result = $this->db->sql_query_limit($sql, $per_page, $start);
        $rowset = $this->db->sql_fetchrowset($result);
        $this->db->sql_freeresult($result);
        return [$rowset, $total];
    }

    public function select_open_requests($per_page, $start, $user_id=null)
    {
        $maxi_query = 300;
        if ($user_id===null)
        {
            $user_id = $this->user->data['user_id'];
        }
        $tbl = $this->container->getParameter('jeb.snahp.tables');
        $def = $this->container->getParameter('jeb.snahp.req')['def'];
        $def_closed = $def['set']['closed'];
        $sql = 'SELECT 
            r.tid, r.fid, r.pid, r.created_time,
            r.requester_uid, r.status,
            t.topic_title
            FROM
                ('. $tbl['req'] . ' r)
            LEFT JOIN ('. TOPICS_TABLE .' t) 
            ON (t.topic_id=r.tid)
            WHERE
                r.requester_uid=' . $user_id . '
            AND ' . $this->db->sql_in_set('status', $def_closed, true) .'
            ORDER BY r.created_time DESC';
        $result = $this->db->sql_query_limit($sql, $maxi_query);
        $rowset = $this->db->sql_fetchrowset($result);
        $total = count($rowset);
        $this->db->sql_freeresult($result);
        $result = $this->db->sql_query_limit($sql, $per_page, $start);
        $rowset = $this->db->sql_fetchrowset($result);
        $this->db->sql_freeresult($result);
        return [$rowset, $total];
    }

    public function select_thanks_given($per_page, $start, $user_id=null)
    {
        $maxi_query = 300;
        if ($user_id===null)
        {
            $user_id = $this->user->data['user_id'];
        }
        $tbl = $this->container->getParameter('jeb.snahp.tables');
        $sql = 'SELECT 
            t.topic_id, t.post_id, t.poster_id, t.forum_id,
            t.thanks_time,
            u.username, u.user_colour,
            p.post_time, p.post_subject, p.post_time
            FROM
                ('. $tbl['thanks'] . ' t)
                    LEFT JOIN ' .
                POSTS_TABLE . ' p ON (p.post_id = t.post_id)
                LEFT JOIN ' .
                USERS_TABLE . ' u ON (u.user_id = t.poster_id)
            WHERE
                t.user_id = ' . $user_id . '
            ORDER BY t.thanks_time DESC';
        $result = $this->db->sql_query_limit($sql, $maxi_query);
        $rowset = $this->db->sql_fetchrowset($result);
        $total = count($rowset);
        $this->db->sql_freeresult($result);
        $result = $this->db->sql_query_limit($sql, $per_page, $start);
        $rowset = $this->db->sql_fetchrowset($result);
        $this->db->sql_freeresult($result);
        return [$rowset, $total];
    }

    public function select_one_day($parent_id, $per_page, $start, $sort_mode)
    {
        $a_fid = $this->select_subforum($parent_id);
        $maxi_query = $this->config['snp_ql_fav_limit'];
        $timedelta = $this->config['snp_ql_fav_duration'];
        $time = time();
        $lastdt = $time - $timedelta;
        switch ($sort_mode)
        {
        case 'views':
            $order_by = 't.topic_views DESC';
            $where = $this->db->sql_in_set('t.forum_id', $a_fid);
            $where .= ' AND t.topic_time>' . $lastdt;
            break;
        case 'replies':
            $order_by = 't.topic_posts_approved DESC';
            $where = $this->db->sql_in_set('t.forum_id', $a_fid);
            $where .= ' AND t.topic_time>' . $lastdt;
            break;
        case 'id':
        default:
            // $order_by = 't.topic_id DESC';
            $order_by = 't.topic_time DESC';
            $where = $this->db->sql_in_set('t.forum_id', $a_fid);
            $where .= ' AND t.topic_time>' . $lastdt;
            break;
        }
        $sql_array = [
            'SELECT'	=> '
                    t.forum_id, topic_id, topic_title, topic_views, topic_time, 
                    topic_visibility, topic_posts_approved,
                    topic_poster, topic_first_poster_name, topic_first_poster_colour,
                    topic_last_poster_id, topic_last_poster_name, topic_last_poster_colour,
                    topic_last_post_subject, topic_last_post_time,
                    forum_name',
            'FROM'		=> [ TOPICS_TABLE	=> 't', ],
            'LEFT_JOIN'	=> [
                [
                    'FROM'	=> [FORUMS_TABLE => 'f'],
                    'ON'	=> 't.forum_id=f.forum_id',
                ],
            ],
            'WHERE'		=> $where,
            'ORDER_BY' => $order_by,
        ];
        $sql = $this->db->sql_build_query('SELECT', $sql_array);
        $result = $this->db->sql_query_limit($sql, $maxi_query);
        $rowset = $this->db->sql_fetchrowset($result);
        $total = count($rowset);
        $this->db->sql_freeresult($result);
        $result = $this->db->sql_query_limit($sql, $per_page, $start);
        $rowset = $this->db->sql_fetchrowset($result);
        $this->db->sql_freeresult($result);
        return [$rowset, $total];
    }

    // TOPIC
    public function select_topic($tid)
    {
        $sql = 'SELECT * FROM ' . TOPICS_TABLE ." WHERE topic_id=$tid";
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        return $row;
    }

    public function update_topic($tid, $data)
    {
        $sql = 'UPDATE ' . TOPICS_TABLE . '
            SET ' . $this->db->sql_build_array('UPDATE', $data) . '
            WHERE topic_id=' . $tid;
        $this->db->sql_query($sql);
    }

    public function update_topic_title($fid, $tid, $pid, $ptn, $repl)
    {
        $topicdata = $this->select_topic($tid);
        if (!$topicdata)
        {
            return 'That topic doesn\'t exist';
        }
        $topic_last_pid = $topicdata['topic_last_post_id'];
        $topic_title = $topicdata['topic_title'];
        $topic_title = preg_replace($ptn, '', $topic_title);
        $topic_title = implode(' ', [$repl, $topic_title]);
        $this->update_topic($tid, [
            'topic_title' => $topic_title,
            'topic_last_post_subject' => $topic_title,
        ]);
        if ($postdata = $this->select_post($pid, 'post_subject'))
        {
            $post_subject = $postdata['post_subject'];
            $post_subject = preg_replace($ptn, '', $post_subject);
            $post_subject = implode(' ', [$repl, $post_subject]);
            $this->update_post([$topic_last_pid, $pid], ['post_subject' => $post_subject,]);
            $this->update_forum_last_post($fid);
        }
        return false;
    }

    // Forum
    public function select_forum($pid, $field='*')
    {
        $sql = 'SELECT '. $field . ' FROM ' . FORUMS_TABLE ." WHERE forum_id=$pid";
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        return $row;
    }

    public function update_forum_last_post($fid)
    {
        include_once('includes/functions_posting.php');
        $type = 'forum';
        $ids = [$fid];
        update_post_information($type, $ids, $return_update_sql = false);
    }

    // POST
    public function select_post($pid, $field='*')
    {
        $sql = 'SELECT '. $field . ' FROM ' . POSTS_TABLE ." WHERE post_id=$pid";
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        return $row;
    }

    public function update_post($pid, $data)
    {
        $sql = 'UPDATE ' . POSTS_TABLE . '
            SET ' . $this->db->sql_build_array('UPDATE', $data) . '
            WHERE ' . $this->db->sql_in_set('post_id', $pid);
        $this->db->sql_query($sql);
    }

    // USERS
    public function select_user_by_username($username)
    {
        $sql = 'SELECT * FROM ' . USERS_TABLE ." WHERE username_clean='$username'";
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        return $row;
    }

    public function update_user($user_id, $data)
    {
        $sql = 'UPDATE ' . USERS_TABLE . '
            SET ' . $this->db->sql_build_array('UPDATE', $data) . '
            WHERE user_id=' . $user_id;
        $this->db->sql_query($sql);
    }

    public function select_user($user_id)
    {
        $sql = 'SELECT * FROM ' . USERS_TABLE ." WHERE user_id=$user_id";
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        return $row;
    }

    // GROUP
    public function select_groups()
    {
        $sql = 'SELECT * FROM ' . GROUPS_TABLE;
        $result = $this->db->sql_query($sql);
        $rowset = $this->db->sql_fetchrowset($result);
        $this->db->sql_freeresult($result);
        return $rowset;
    }

    public function select_group($gid)
    {
        $sql = 'SELECT * FROM ' . GROUPS_TABLE . " WHERE group_id=$gid";
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        return $row;
    }

    // REQUEST USERS
    public function select_request_users_by_username($username)
    {
        $username = utf8_clean_string($username);
        $userdata = $this->select_user_by_username($username);
        if (!$userdata) return [];
        $user_id = $userdata['user_id'];
        $tbl = $this->container->getParameter('jeb.snahp.tables');
        $sql = 'SELECT * FROM ' . $tbl['requsr'] ." WHERE user_id=$user_id";
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        return $row;
    }

    public function select_request_users($user_id)
    {
        $tbl = $this->container->getParameter('jeb.snahp.tables');
        $sql = 'SELECT * FROM ' . $tbl['requsr'] ." WHERE user_id=$user_id";
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        return $row;
    }

    public function update_request_users($user_id, $data)
    {
        $tbl = $this->container->getParameter('jeb.snahp.tables');
        $sql = 'UPDATE ' . $tbl['requsr'] . '
            SET ' . $this->db->sql_build_array('UPDATE', $data) . '
            WHERE user_id = ' . $user_id;
        $this->db->sql_query($sql);
    }

    // REQUEST
    public function select_request_closed()
    {
        $tbl = $this->container->getParameter('jeb.snahp.tables');
        $def = $this->container->getParameter('jeb.snahp.req')['def'];
        $def_closed = $def['set']['closed'];
        $sql = 'SELECT * FROM ' . $tbl['req'] .
            ' WHERE ' . $this->db->sql_in_set('status', $def_closed) .
            ' AND b_graveyard = 0';
        $result = $this->db->sql_query($sql);
        $data = $this->db->sql_fetchrowset($result);
        $this->db->sql_freeresult($result);
        return $data;
    }

    public function select_request_open_by_uid($uid)
    {
        $tbl = $this->container->getParameter('jeb.snahp.tables');
        $def = $this->container->getParameter('jeb.snahp.req')['def'];
        $def_closed = $def['set']['closed'];
        $sql = 'SELECT * FROM ' . $tbl['req'] .
            ' WHERE ' . $this->db->sql_in_set('status', $def_closed, true) .
            ' AND b_graveyard = 0 AND requester_uid=' . $uid;
        $result = $this->db->sql_query_limit($sql, 20);
        $data = $this->db->sql_fetchrowset($result);
        $this->db->sql_freeresult($result);
        return $data;
    }

    public function select_request($tid)
    {
        $tbl = $this->container->getParameter('jeb.snahp.tables');
        $sql = 'SELECT * FROM ' . $tbl['req'] . " WHERE tid=$tid";
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        return $row;
    }

    public function update_request($tid, $data)
    {
        $tbl = $this->container->getParameter('jeb.snahp.tables');
        $sql = 'UPDATE ' . $tbl['req'] . '
            SET ' . $this->db->sql_build_array('UPDATE', $data) . '
            WHERE tid=' . $tid;
        $this->db->sql_query($sql);
    }

    // Dibs
    public function insert_dibs($data)
    {
        $tbl = $this->container->getParameter('jeb.snahp.tables');
        $sql = 'INSERT INTO ' . $tbl['dibs'] .
            $this->db->sql_build_array('INSERT', $data);
        $this->db->sql_query($sql);
    }

    public function update_dibs($tid, $data)
    {
        $tbl = $this->container->getParameter('jeb.snahp.tables');
        $sql = 'UPDATE ' . $tbl['dibs'] . '
            SET ' . $this->db->sql_build_array('UPDATE', $data) . '
            WHERE tid=' . $tid . '
            ORDER BY id DESC';
        $this->db->sql_query($sql);
    }

    public function select_dibs($tid)
    {
        $tbl = $this->container->getParameter('jeb.snahp.tables');
        $sql_ary = [
            'SELECT'   => '*',
            'FROM'     => [$tbl['dibs'] => 'dibs'],
            'WHERE'    => 'tid=' . $tid,
            'ORDER_BY' => 'id DESC',
        ];
        $sql    = $this->db->sql_build_query('SELECT', $sql_ary);
        $result = $this->db->sql_query($sql);
        $row    = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        return $row;
    }

    public function select_total($tbl, $condition)
    {
        $sql_ary = [
            'SELECT'   => 'count(*) as total',
            'FROM'     => [$tbl => 'dibs'],
            'WHERE'    => $condition,
        ];
        $sql    = $this->db->sql_build_query('SELECT', $sql_ary);
        $result = $this->db->sql_query($sql);
        $row    = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        return $row['total'];
    }

    public function select_last_undib($undibber_uid, $tid)
    {
        $tbl = $this->container->getParameter('jeb.snahp.tables');
        $def = $this->container->getParameter('jeb.snahp.req')['def'];
        $sql_ary = [
            'SELECT'   => '*',
            'FROM'     => [$tbl['dibs'] => 'dibs'],
            'WHERE'    => 'status=' . $def['undib'] . '
                        AND undibber_uid=' . $undibber_uid . '
                        AND tid=' . $tid,
            'ORDER_BY' => 'id DESC'
        ];
        $sql    = $this->db->sql_build_query('SELECT', $sql_ary);
        $result = $this->db->sql_query($sql);
        $row    = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        return $row;
    }

    public function reject_bots()
    {
        // $BOT_GID = 6
        $BOTS_GID = 6;
        $gid = $this->user->data['group_id'];
        if (!$gid || $gid == $BOTS_GID)
            trigger_error('Access to bots has been denied.');
    }

    public function reject_anon()
    {
        $uid = $this->user->data['user_id'];
        if ($uid == ANONYMOUS)
            trigger_error('You must login before venturing forth.');
    }

    public function is_admin()
    {
        return $this->auth->acl_gets('a_');
    }

    public function is_mod()
    {
        return $this->auth->acl_gets('a_', 'm_');
    }

    public function is_self($user_id)
    {
        return $user_id==$this->user->data['user_id'];
    }

    public function is_op($topic_data)
    {
        $poster_id = $topic_data['topic_poster'];
        $user_id = $this->user->data['user_id'];
        return $poster_id == $user_id;
    }

    public function reject_non_admin($append='')
    {
        if (!$this->is_admin())
            trigger_error('Only administrator may access this page. ' . $append);
    }

    public function reject_non_moderator($append='')
    {
        if (!$this->is_mod())
            trigger_error('Only moderators may access this page. ' . $append);
    }

    public function reject_non_group($group_id, $perm_name)
    {
        $sql = 'SELECT 1 FROM ' . GROUPS_TABLE . '
            WHERE group_id=' . $group_id . ' AND 
            ' . $perm_name . '=1';
        $result = $this->db->sql_query($sql, 1);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        if (!$row)
            trigger_error('Your don\'t have the permission to access this page.');
    }

    public function is_search_enhancer_allowed($topic_data)/*{{{*/
    {
        // Search enhancer is allowed for:
        // 1) members in the allowed group (in any forum)
        // 2) OP of a topic only in the listings forums
        if (!$this->config['snp_search_b_enable'])
            return false;
        if (!$this->config['snp_search_b_enhancer'])
            return false;
        $gid = $this->user->data['group_id'];
        $gd = $this->select_group($gid);
        $b_group = $gd['snp_search_index_b_enable'];
        if ($b_group)
        {
            return true;
        }
        $b_op = $this->is_op($topic_data);
        if (!$b_op)
        {
            return false;
        }
        $b_listing = $this->is_listing($topic_data, 'topic_data');
        if ($b_listing)
        {
            return true;
        }
        return false;
    }/*}}}*/

    public function is_listing($var, $mode='topic_data')/*{{{*/
    {
        // To check if topic is in listings
        $cache_time = 5;
        $fid_listings = $this->config['snp_fid_listings'];
        switch ($mode)
        {
        case 'topic_id':
            $topic_data = $this->select_topic((int)$var);
            break;
        case 'topic_data':
            $topic_data = $var;
            break;
        default:
            return false;
        }
        if (!$topic_data)
        {
            return false;
        }
        $forum_id = $topic_data['forum_id'];
        $sub = $this->select_subforum($fid_listings, $cache_time);
        $topic_id = $topic_data['topic_id'];
        $res = in_array($forum_id, $sub) ? true : false;
        return $res;
    }/*}}}*/

    public function is_request($var, $mode='request_data')/*{{{*/
    {
        // To check if topic is in requests
        $cache_time = 5;
        $fid_requests = $this->config['snp_fid_requests'];
        switch ($mode)
        {
        case 'request_id':
            $topic_data = $this->select_topic((int)$var);
            break;
        case 'request_data':
            $topic_data = $var;
            break;
        default:
            return false;
        }
        if (!$topic_data)
        {
            return false;
        }
        $forum_id = $topic_data['forum_id'];
        $sub = $this->select_subforum($fid_requests, $cache_time);
        $topic_id = $topic_data['topic_id'];
        $res = in_array($forum_id, $sub) ? true : false;
        return $res;
    }/*}}}*/

    public function get_dt($time)
    {
        return $this->user->format_date($time);
    }

    public function get_config_array($key)
    {
        $config_text = $this->container->get('config_text');
        $data = unserialize($config_text->get($key));
        return $data;
    }

    public function get_fid($name)
    {
        $config_text = $this->container->get('config_text');
        $snp_fid = unserialize($config_text->get('snp_fid'));
        $fid =  array_key_exists($name, $snp_fid) ? $snp_fid[$name] : 0;
        return (int) $fid;
    }

    public function make_username($row)
    {
        $strn = '<a href="/memberlist.php?mode=viewprofile&u='. $row['user_id'] . '" style="color: #'. $row['user_colour'] .'">' . $row['username'] . '</a>';
        return $strn;
    }

    public function add_host_icon($strn)
    {
        $ptn = '#(\[(android)\])#is';
        $strn = preg_replace($ptn, '<img class="android_icon" src="https://i.imgur.com/uBsdomR.png">', $strn);
        $ptn = '#(\[(ios)\])#is';
        $strn = preg_replace($ptn, '<img class="ios_icon" src="https://i.imgur.com/mJ4Rmz1.png">', $strn);
        $ptn = '#(\[(mega)\])#is';
        $strn = preg_replace($ptn, '<img class="mega_icon" src="https://i.imgur.com/w5aP33F.png">', $strn);
        $ptn = '#(\[(gdrive|gd)\])#is';
        $strn = preg_replace($ptn, '<img class="gdrive_icon" src="https://i.imgur.com/VQv2dUm.png">', $strn);
        $ptn = '#(\[(zippy|zs|zippyshare)\])#is';
        // $strn = preg_replace($ptn, '<img class="zippy_icon" src="https://i.imgur.com/uJXaUNG.png">', $strn);
        $strn = preg_replace($ptn, '<img class="zippy_icon" src="https://i.imgur.com/qD95AzT.png">', $strn);
        return $strn;
    }

    public function add_tag($strn)
    {
        $ptn = '#((\(|\[|\{)(request)(\)|\]|\}))#is';
        $strn = preg_replace($ptn, '<span class="btn open">\1</span>', $strn);
        $ptn = '#((\(|\[|\{)(solved)(\)|\]|\}))#is';
        $strn = preg_replace($ptn, '<span class="btn solve">\1</span>', $strn);
        $ptn = '#((\(|\[|\{)(accepted)(\)|\]|\}))#is';
        $strn = preg_replace($ptn, '<span class="btn dib">\1</span>', $strn);
        $ptn = '#((\(|\[|\{)(fulfilled)(\)|\]|\}))#is';
        $strn = preg_replace($ptn, '<span class="btn fulfill">\1</span>', $strn);
        $ptn = '#((\(|\[|\{)(closed)(\)|\]|\}))#is';
        $strn = preg_replace($ptn, '<span class="btn terminate">\1</span>', $strn);
        return $strn;
    }

    public function remove_tag($strn)
    {
        $ptn = '#<span[^>]*"(btn){1}.*">([^<]*)</span>#is';
        $strn = preg_replace($ptn, '', $strn);
        return $strn;
    }

    public function validate_curly_tags($html)
    {
        preg_match_all('#{([a-z]+)(?: .*)?(?<![/|/ ])}#iU', $html, $result);
        $openedtags = $result[1];   #put all closed tags into an array
        preg_match_all('#{/([a-z]+)}#iU', $html, $result);
        $closedtags = $result[1];
        $len_opened = count($openedtags);
        $len_closed = count($closedtags);
        if ($len_closed != $len_opened) {
            return False;
        }
        $openedtags = array_reverse($openedtags);
        for ($i=0; $i < $len_opened; $i++)
        {
            if (!in_array($openedtags[$i], $closedtags))
            {
                return False;
            }
            else
            {
                unset($closedtags[array_search($openedtags[$i], $closedtags)]);
            }
        }
        return True;
    }

    public function interpolate_curly_table_autofill($strn)
    {
        // $strn = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $strn);
        $ptn = '#{table_autofill}(.*?){/table_autofill}#is';
        preg_match($ptn, $strn, $match);
        $content = $match[1];
        $content = preg_replace("#<br>#", PHP_EOL, $content);
        $arr = explode(PHP_EOL, $content);
        $res = [];
        $res[] = '<style>td.hidden {display: none}</style>';
        $res[] = '<table class="autofill search">';
        $res[] = '<thead><tr><th></th></tr></thead>';
        $res[] = '<tbody>';
        foreach($arr as $entry)
        {
            if ($entry)
            {
                $res[] = "<tr><td style='text-align:left;'>$entry</td></tr>";
            }
        }
        $res[] = '</tbody></table>';
        $res = implode(PHP_EOL, $res);
        $strn = preg_replace($ptn, $res, $strn);
        return $strn;
    }

    public function interpolate_curly_table($strn)
    {
        $ptn = '#{([^}]*)}#is';
        $strn = preg_replace_callback($ptn, function($m) {
            $allowed_directive = $this->allowed_directive;
            $sub = $m[1];
            $b_open = False;
            if ($sub && $sub[0] == '/')
            {
                $sub = substr($sub, 1);
            }
            else
            {
                $b_open = True;
            }
            preg_match('/(\w+)/is', $sub, $match);
            if ($match && in_array($match[0], $allowed_directive))
            {
                switch ($match[0])
                {
                case 'table':
                    if ($b_open)
                    {
                        $tag = '<div class="request_table container"><div class="request_table wrapper">';
                        $tag .= "<$m[1]>";
                    }
                    else
                    {
                        $tag = "<$m[1]>";
                        $tag .= '</div></div>';
                    }
                    return $tag;
                    break;
                case 'iframe':
                    $ptn = '#src=("|\')([^("|\')]*)("|\')#is';
                    $text = $m[1];
                    preg_match($ptn, $m[1], $match);
                    if (!$match)
                    {
                        return "<$m[1]>";
                    }
                    $url = parse_url($match[2]);
                    $allowed_hosts = ['www.youtube.com'];
                    if (in_array($url['host'], $allowed_hosts))
                    {
                        return "<$m[1]>";
                    }
                    return '';
                    break;
                default:
                    return "<$m[1]>";
                }
            }
        }, $strn);
        $strn = $strn[0];
        $ptn = '#(.*)(<table.*</table>)(.*)#is';
        preg_match($ptn, $strn, $match);
        if ($match)
        {
            $table = $match[2];
            $table = str_replace('<br>', '', $table);
            $strn = $match[1];
            $strn .= $table;
            $strn .= $match[3];
        }
        return $strn;
    }

    public function replace_snahp($strn)
    {
        $parser = new \jeb\snahp\core\curly_parser();
        $strn = $parser->parse_snahp($strn);
        // $strn = preg_replace_callback($ptn, [&$this, 'interpolate_curly_table'], $strn);
        // $strn = preg_replace_callback('#.*#', [&$this, 'interpolate_curly_table'], $strn);
        return $strn;
    }
    
    public function interpolate_curly_tags($strn)
    {
        $valid = $this->validate_curly_tags($strn) ? 1 : 0;
        if (!$valid) return $strn;
        $strn = $this->replace_snahp($strn);
        return $strn;
    }

    public function interpolate_curly_tags_deprecated($strn)
    {
        $valid = $this->validate_curly_tags($strn) ? 1 : 0;
        if (!$valid) return $strn;
        $ptn = '#({snahp})(.*?)({/snahp})#is';
        $strn = preg_replace_callback($ptn, [&$this, 'interpolate_curly_table'], $strn);
        $strn = preg_replace_callback('#.*#', [&$this, 'interpolate_curly_table'], $strn);
        $ptn = '#(.*)(<table.*</table>)(.*)#is';
        preg_match($ptn, $strn, $match);
        if ($match)
        {
            $table = $match[2];
            $table = str_replace('<br>', '', $table);
            $strn = $match[1];
            $strn .= $table;
            $strn .= $match[3];
        }
        return $strn;
    }

}
