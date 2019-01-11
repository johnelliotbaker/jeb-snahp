<?php

use \Symfony\Component\HttpFoundation\Response;

namespace jeb\snahp\controller;

use phpbb\user;
use phpbb\auth\auth;
use phpbb\request\request_interface;
use phpbb\db\driver\driver_interface;
use phpbb\notification\manager;

function prn($var) {
    if (is_array($var))
    { foreach ($var as $k => $v) { echo "... $k => "; prn($v); }
    } else { echo "$var<br>"; }
}

class dibs
{
	protected $u_action;
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

    /**
     * Constructor
     *
     * @param \phpbb\config\config      $config
     * @param \phpbb\controller\helper  $helper
     * @param \phpbb\language\language  $language
     * @param \phpbb\template\template  $template
     */
    public function __construct(
        user $user,
        auth $auth,
        request_interface $request,
        driver_interface $db,
        \phpbb\config\config $config,
        \phpbb\controller\helper $helper,
        \phpbb\language\language $language,
        \phpbb\template\template $template,
        $table_prefix,
        manager $notification
    )
    {
        $this->user     = $user;
        $this->auth     = $auth;
        $this->request  = $request;
        $this->config   = $config;
        $this->helper   = $helper;
        $this->language = $language;
        $this->template = $template;
        $this->db       = $db;
        $this->u_action = '';
        $this->table_prefix = $table_prefix;
        $this->notification = $notification;
    }

	public function handle($fid, $tid, $pid, $mode)
    {
        $this->u_action = "viewtopic.php?f=".$fid."&t=".$tid."&p=".$pid;
        $this->reject_anon();
        switch ($mode)
        {
            case 1:
                $this->reject_dibbed($tid);
                $this->call_dibs($fid, $tid, $pid);
                break;
            case 2:
                $this->mark_solved($fid, $tid, $pid);
                break;
            case 3:
                $this->confirm_solved_by_requester($fid, $tid, $pid);
                break;
        }
        // return $this->helper->render('snahp_dibs.html');
    }

    public function confirm_solved_by_requester($fid, $tid, $pid)
    {
        // Set confirmed_time
        $time = time();
        $sql = 'UPDATE ' . $this->table_prefix . 'snahp_dibs SET confirmed_time=' . $time . ' WHERE tid=' . $tid;
        $result = $this->db->sql_query($sql);
        $this->db->sql_freeresult($result);
        // Change topic title to add [solved] and remove [request] or [accepted]
        $sql = 'SELECT topic_title FROM ' . TOPICS_TABLE . ' WHERE topic_id=' . $tid;
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        $topic_title = $row['topic_title'];
        $topic_title = preg_replace('#\[(accepted|request|solved)\]#is', '', $topic_title);
        $topic_title = implode(' ', ['[Solved]', $topic_title]);
        // $topic_title = preg_replace('#\[request\]#is', '', $topic_title);
        $sql = 'UPDATE ' . TOPICS_TABLE . ' SET topic_title="' . $topic_title . '"' . ' WHERE topic_id=' . $tid;
        $result = $this->db->sql_query($sql);
        $this->db->sql_freeresult($result);
        meta_refresh(3, $this->u_action);
        trigger_error("This request has been marked as solved.");
    }

    public function send_notification($fid, $tid, $pid, $requester_uid)
    {
        $user_id  = $this->user->data['user_id'];
        $username = $this->user->data['username'];
        $username_string = get_username_string('no_profile', $user_id, $username, $this->user->data['user_colour']);
        $receiver_id = $requester_uid;
        $text     = $username_string . ' fulfilled your request.<br>Click to visit the topic<br>and press <i class="icon fa-check-square fa-fw" aria-hidden="true"></i> to close it.';
        $data = array(
            'user_id'      => $user_id,
            'username'     => $username,
            'post_id'      => $pid,
            'poster_id'    => $receiver_id,
            'topic_id'     => $tid,
            'forum_id'     => $fid,
            'time'         => time(),
            'post_subject' => $text,
        );
        $this->notification->add_notifications(array(
            'jeb.snahp.notification.type.basic',
        ), $data);
    }

	public function mark_solved($fid, $tid, $pid)
    {
        $sql = 'SELECT * FROM ' . $this->table_prefix . 'snahp_dibs
            WHERE tid=' . $tid;
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        $fulfilled_time = $row['fulfilled_time'];
        $requester_uid = $row['requester_uid'];
        $uid = $row['fulfiller_uid'];
        $username = $row['fulfiller_username'];
        $user_colour = $row['fulfiller_colour'];
        $username_string = get_username_string('no_profile', $uid, $username, $user_colour);
        if (!$fulfilled_time)
        {
            // Set fulfilled_time
            $time = time();
            $sql = 'UPDATE ' . $this->table_prefix . 'snahp_dibs SET fulfilled_time=' . $time . ' WHERE tid=' . $tid;
            $result = $this->db->sql_query($sql);
            $this->db->sql_freeresult($result);
            // Send notification to the requester
            $this->send_notification($fid, $tid, $pid, $requester_uid);
            meta_refresh(3, $this->u_action);
            trigger_error("Thank you for fulfilling this request $username_string");
        }
        $dt = new \DateTime(date('r', $fulfilled_time));
        $datetime = $dt->format('m/d H:i');
        $strn = "$username_string fulfilled this request $datetime.";
        meta_refresh(3, $this->u_action);
        trigger_error($strn);
    }

    public function reject_anon()
    {
        if ($this->user->data['user_id'] == ANONYMOUS)
            trigger_error("You must login before venturing forth.");
    }

    public function reject_dibbed($tid)
    {
        $sql = 'SELECT * FROM ' . $this->table_prefix . 'snahp_dibs
            WHERE tid=' . $tid;
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        if ($row)
        {
            $username = $row['fulfiller_username'];
            $uid = $row['fulfiller_uid'];
            $user_colour = $row['fulfiller_colour'];
            $username_string = get_username_string('no_profile', $uid, $username, $user_colour);
            $strn = $username_string . ' already called dibs on this request!';
            meta_refresh(3, $this->u_action);
            trigger_error($strn);
        }
    }

	public function call_dibs($fid, $tid, $pid)
    {
        $sql = 'SELECT * FROM ' . TOPICS_TABLE . '
            WHERE topic_id=' . $tid;
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        if (!$row)
        {
            meta_refresh(6, $this->u_action);
            trigger_error("That topic doesn't exist");
        }
        $topic_title = $row['topic_title'];
        if (!preg_match('#\[Accepted\]#is', $topic_title))
        {
            $topic_title = '[Accepted] ' . $topic_title;
            $sql = 'UPDATE ' . TOPICS_TABLE . ' SET topic_title="' . $topic_title . '" WHERE topic_id=' . $tid;
            $result = $this->db->sql_query($sql);
            $this->db->sql_freeresult($result);
        }
        $sql_ary = [
            'fid'                => $fid,
            'tid'                => $tid,
            'pid'                => $pid,
            'requester_uid'      => $row['topic_poster'],
            'fulfiller_uid'      => $this->user->data['user_id'],
            'fulfiller_username' => $this->user->data['username'],
            'fulfiller_colour'   => $this->user->data['user_colour'],
            'commit_time'        => time(),
            'fulfilled_time'     => null,
        ];
        $sql = 'INSERT INTO ' . $this->table_prefix . 'snahp_dibs ' .
            $this->db->sql_build_array('INSERT', $sql_ary);
        $result = $this->db->sql_query($sql);
        $this->db->sql_freeresult($result);
        meta_refresh(2, $this->u_action);
        $message = "You called dibs on a request! Thanks a lot for your help.";
        trigger_error($message);
    }

}
