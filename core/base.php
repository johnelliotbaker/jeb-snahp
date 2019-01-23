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

function prn($var) {
    if (is_array($var))
    { foreach ($var as $k => $v) { echo "... $k => "; prn($v); }
    } else { echo "$var<br>"; }
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
        $this->table_prefix = 'phpbb_';
    }
    public function set_notification(manager $manager)
    {
        $this->notification = $manager;
    }

    // DATABASE Functions
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

    public function select_user($user_id)
    {
        $sql = 'SELECT * FROM ' . USERS_TABLE ." WHERE user_id=$user_id";
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        return $row;
    }

    // GROUP
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

    public function reject_anon()
    {
        $uid = $this->user->data['user_id'];
        if ($uid == ANONYMOUS)
            trigger_error('You must login before venturing forth.');
    }

    public function is_mod()
    {
        return $this->auth->acl_gets('a_', 'm_');
    }

    public function reject_non_moderator()
    {
        if (!$this->is_mod())
            trigger_error('Only moderators can access this page.');
    }

    public function get_dt($time)
    {
        $dt = new \DateTime(date('r', $time));
        return $dt->format('Y/m/d h:i a');
    }

    public function add_tag($strn)
    {
        $strn = str_replace('[Request]', '<span class="btn open">[Request]</span>', $strn);
        $strn = str_replace('[Solved]', '<span class="btn solve">[Solved]</span>', $strn);
        $strn = str_replace('[Accepted]', '<span class="btn dib">[Accepted]</span>', $strn);
        $strn = str_replace('[Fulfilled]', '<span class="btn fulfill">[Fulfilled]</span>', $strn);
        $strn = str_replace('[Closed]', '<span class="btn terminate">[Closed]</span>', $strn);
        return $strn;
    }

    public function remove_tag($strn)
    {
        $ptn = '#<span[^>]*"(btn){1}.*">([^<]*)</span>#is';
        $strn = preg_replace($ptn, '', $strn);
        return $strn;
    }

}

