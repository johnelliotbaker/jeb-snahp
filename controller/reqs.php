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

class reqs
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
    protected $req_tbl;
    protected $req_users_tbl;
    protected $status_definition;


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
        $this->table_prefix  = $table_prefix;
        $this->notification  = $notification;
        $this->req_tbl       = $table_prefix . 'snahp_request';
        $this->req_users_tbl = $table_prefix . 'snahp_request_users';
        $this->status_definition = [
            'open' => 1, 'solve' => 2, 'dib' => 3, 'fulfill' => 4, 'terminate' => 9,];
    }

	public function handle($fid, $tid, $pid, $mode)
    {
        $this->u_action = "viewtopic.php?f=$fid&t=$tid&p=$pid";
        $this->reject_anon();
        $def = $this->status_definition;
        switch ($mode)
        {
            case $def['solve']: // To solve request
                $this->solve_request($fid, $tid, $pid, $def['solve']);
                break;
            case $def['terminate']: // To terminate request
                $this->solve_request($fid, $tid, $pid, $def['terminate']);
                break;
            case $def['dib']:
                $this->dib_request($fid, $tid, $pid);
                break;
            case $def['fulfill']:
                $this->fulfill_request($fid, $tid, $pid);
                break;
        }
        // return $this->helper->render('snahp_dibs.html');
    }

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
        $result = $this->db->sql_query($sql);
        $this->db->sql_freeresult($result);
    }

    public function select_request_users($uid)
    {
        $sql = 'SELECT * FROM ' . $this->req_users_tbl ." WHERE user_id=$uid";
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        return $row;
    }

    public function update_request_users($uid, $data)
    {
        $sql = 'UPDATE ' . $this->req_users_tbl . '
            SET ' . $this->db->sql_build_array('UPDATE', $data) . '
            WHERE user_id=' . $uid;
        $result = $this->db->sql_query($sql);
        $this->db->sql_freeresult($result);
    }

    public function increment_request_users_slot($tid, $uid, $data, $slot=1)
    {
        $ru = $this->select_request_users($uid);
        $nl = $ru['n_left'];
        $nb = $ru['n_base'];
        $no = $ru['n_offset'];
        if ($nl > $nb)
        {
            $no += $nl - $nb;
            $nl = $nb;
        }
        $dl = min($nb - $nl, $slot);
        $do = $slot - $dl;
        $nl += $dl;
        $no += $do;
        $sql = 'UPDATE ' . $this->req_users_tbl . "
            SET n_left=$nl, n_offset=$no " . '
            WHERE user_id=' . $uid;
        $result = $this->db->sql_query($sql);
        $this->db->sql_freeresult($result);
    }

    public function select_request($tid)
    {
        $sql = 'SELECT * FROM ' . $this->req_tbl ." WHERE tid=$tid";
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        return $row;
    }

    public function update_request($tid, $data)
    {
        $sql = 'UPDATE ' . $this->req_tbl . '
            SET ' . $this->db->sql_build_array('UPDATE', $data) . '
            WHERE tid=' . $tid;
        $result = $this->db->sql_query($sql);
        $this->db->sql_freeresult($result);
    }

    public function remove_request($tid)
    {
        $data = ['tid' => $tid];
        $sql = 'DELETE FROM ' . $this->req_tbl . $this->db->sql_build_array('DELETE', $data); 
        $result = $this->db->sql_query($sql);
        $this->db->sql_freeresult($result);
    }

    // public function select_dibs($tid)
    // {
    //     $sql = 'SELECT * FROM ' . $this->dibs_tbl ." WHERE tid=$tid";
    //     $result = $this->db->sql_query($sql);
    //     $row = $this->db->sql_fetchrow($result);
    //     $this->db->sql_freeresult($result);
    //     return $row;
    // }
    //
    // public function update_dibs($tid, $data)
    // {
    //     $sql = 'UPDATE ' . $this->dibs_tbl . '
    //         SET ' . $this->db->sql_build_array('UPDATE', $data) . '
    //         WHERE tid=' . $tid;
    //     $result = $this->db->sql_query($sql);
    //     $this->db->sql_freeresult($result);
    // }

    public function solve_request($fid, $tid, $pid, $new_status)
    {
        // Set confirmed_time
        $req = $this->select_request($tid);
        $def = $this->status_definition;
        $resolvedStatus = [$def['solve'], $def['terminate']];
        if (!$req)
        {
			meta_refresh(3, $this->u_action);
			trigger_error('That request does not exist.');
		}
        if (in_array($req['status'], $resolvedStatus))
		{
			meta_refresh(3, $this->u_action);
			trigger_error('That request had already been terminated.');
		}
        $user_id = $this->user->data['user_id'];
        if ($user_id != $req['requester_uid'] && !$this->auth->acl_gets('a_', 'm_'))
        {
            //TODO: Send notification to user if closed by mod
            meta_refresh(3, $this->u_action);
            trigger_error('Only the requester and the moderators are allowed to close this request.');
        }
        $time = time();
        $data = [
            'status' => $new_status,
            'solved_time' => $time,
        ];
        $this->update_request($tid, $data);
        $this->increment_request_users_slot($tid, $req['requester_uid'], ['n_left' => 'n_left + 1']);
        $topicdata = $this->select_topic($tid);
        if ($topicdata)
        {
            $topic_title = $topicdata['topic_title'];
            if ($new_status == $def['solve'])
            {
                $topic_title = preg_replace('#\[(accepted|request|closed|fulfilled)\]#is', '', $topic_title);
                $topic_title = implode(' ', ['[Solved]', $topic_title]);
            }
            elseif ($new_status == $def['terminate'])
            {
                $topic_title = preg_replace('#\[(accepted|request|solved|fulfilled)\]#is', '', $topic_title);
                $topic_title = implode(' ', ['[Closed]', $topic_title]);
            }
            $data = ['topic_title' => $topic_title];
            $this->update_topic($tid, $data);
        }
        meta_refresh(3, $this->u_action);
        if ($new_status == $def['solve'])
        {
            trigger_error('This request was solved.');
        }
        elseif ($new_status == $def['terminate'])
        {
            trigger_error('This request was terminated.');
        }
    }

    public function reject_anon()
    {
        if ($this->user->data['user_id'] == ANONYMOUS)
            trigger_error('You must login before venturing forth.');
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

	public function fulfill_request($fid, $tid, $pid)
    {
        $topicdata = $this->select_topic($tid);
        if (!$topicdata)
        {
            meta_refresh(0.5, $this->u_action);
            trigger_error('That topic doesn\'t exist');
        }
        $topic_title = $topicdata['topic_title'];
        $topic_title = preg_replace('#\[(accepted)\]#is', '', $topic_title);
        $topic_title = implode(' ', ['[Fulfilled]', $topic_title]);
        $this->update_topic($tid, ['topic_title' => $topic_title]);
        $data = [
            'fulfilled_time'        => time(),
            'status' => 4,
        ];
        $this->update_request($tid, $data);
        meta_refresh(2, $this->u_action);
        $message = 'Thank you for fulfilling this request!';
        trigger_error($message);
    }

	public function dib_request($fid, $tid, $pid)
    {
        $topicdata = $this->select_topic($tid);
        if (!$topicdata)
        {
            meta_refresh(0.5, $this->u_action);
            trigger_error('That topic doesn\'t exist');
        }
        $topic_title = $topicdata['topic_title'];
        $topic_title = preg_replace('#\[(request)\]#is', '', $topic_title);
        $topic_title = implode(' ', ['[Accepted]', $topic_title]);
        $this->update_topic($tid, ['topic_title' => $topic_title]);
        $data = [
            'fulfiller_uid'      => $this->user->data['user_id'],
            'fulfiller_username' => $this->user->data['username'],
            'fulfiller_colour'   => $this->user->data['user_colour'],
            'commit_time'        => time(),
            'status' => 3,
        ];
        $this->update_request($tid, $data);
        meta_refresh(2, $this->u_action);
        $message = 'You called dibs on a request! Thanks a lot for your help.';
        trigger_error($message);
    }

}
