<?php
/**
 *
 * snahp. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2018
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace jeb\snahp\event;


use jeb\snahp\core\core;
use phpbb\auth\auth;
use phpbb\template\template;
use phpbb\config\config;
use phpbb\request\request_interface;
use phpbb\db\driver\driver_interface;
use phpbb\notification\manager;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;


/**
 * snahp Event listener.
 */
class request_listener extends core implements EventSubscriberInterface
{
    protected $auth;
    protected $request;
    protected $config;
    protected $db;
    protected $template;
    protected $table_prefix;
    protected $notification;
    protected $sql_limit;
    protected $notification_limit;
    protected $at_prefix;
    protected $req_tbl;
    protected $req_users_tbl;
    protected $dibs_tbl;


    public function __construct(
        auth $auth,
        request_interface $request,
        config $config,
        driver_interface $db,
        template $template,
        $table_prefix,
        manager $notification
    )
    {

        $this->auth               = $auth;
        $this->request            = $request;
        $this->config             = $config;
        $this->db                 = $db;
        $this->template           = $template;
        $this->table_prefix       = $table_prefix;
        $this->notification       = $notification;
        $this->sql_limit          = 10;
        $this->notification_limit = 10;
        $this->req_tbl            = $table_prefix . 'snahp_request';
        $this->req_users_tbl      = $table_prefix . 'snahp_request_users';
        $this->dibs_tbl           = $table_prefix . 'snahp_dibs';
    }

    static public function getSubscribedEvents()
    {
        return array(
            'core.posting_modify_submit_post_after' => 'notify_on_poke',
            'core.viewtopic_assign_template_vars_before'  => array(
                array('show_request_icons', 1),
                array('show_dibs',0),
            ),
            'core.posting_modify_template_vars'     => array(
                array('disable_user_posting', 0),
            ),
            'core.posting_modify_submit_post_after' => 'add_request',
        );
    }

    public function select_dibs($tid)
    {
        $sql = 'SELECT * FROM ' . $this->dibs_tbl ." WHERE tid=$tid";
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        return $row;
    }

    public function select_request($tid)
    {
        $sql = 'SELECT * FROM ' . $this->req_tbl ." WHERE tid=$tid";
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        return $row;
    }

    public function select_open_request($uid)
    {
        $sql = 'SELECT * FROM ' . $this->req_tbl . 
            " WHERE requester_uid=$uid AND " .
            $this->db->sql_in_set('status', [2, 9], true);
        $result = $this->db->sql_query($sql);
        while($row = $this->db->sql_fetchrow($result))
        {
            $data[] = $row;
        }
        $this->db->sql_freeresult($result);
        return $data;
    }

    public function select_user($user_id)
    {
        $sql = 'SELECT * FROM ' . USERS_TABLE ." WHERE user_id=$user_id";
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        return $row;
    }

    public function select_request_user($user_id)
    {
        $sql = 'SELECT * FROM ' . $this->req_users_tbl ." WHERE user_id=$user_id";
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        return $row;
    }

    public function insert_request_user($user_id)
    {
        $data = ['user_id' => $user_id];
        $sql = 'INSERT INTO ' . $this->req_users_tbl . $this->db->sql_build_array('INSERT', $data); 
        $result = $this->db->sql_query($sql);
        $this->db->sql_freeresult($result);
    }

    public function add_request($event)
    {
        prn($event['data']);
        if ($event['mode']!='post') return false;
        $fid = $this->request->variable('f', '');
        $request_fid = explode(',', $this->config['snp_request_fid']);
        if (!in_array($fid, $request_fid)) return false;
        $user_id = $this->user->data['user_id'];
        if (!$row = $this->select_request_user($user_id))
        {
            $this->insert_request_user($user_id);
            $row = $this->select_request_user($user_id);
        }
        if ($row['b_unlimited']) return false;
        if ($row['n_left'] > 0)
            $row['n_left'] -= 1; 
        else
            $row['n_offset'] -= 1;
        $sql = 'UPDATE ' . $this->req_users_tbl . '
            SET ' . $this->db->sql_build_array('UPDATE', $row) . '
            WHERE user_id = ' . $user_id;
        $result = $this->db->sql_query($sql);
        $this->db->sql_freeresult($result);
        $userdata = $this->select_user($user_id);
        $data = [
            'fid'                => $event['data']['forum_id'],
            'tid'                => $event['data']['topic_id'],
            'pid'                => $event['data']['post_id'],
            'requester_uid'      => $userdata['user_id'],
            'requester_username' => $userdata['username'],
            'requester_colour'   => $userdata['user_colour'],
            'created_time'       => time(),
        ];
        $sql = 'INSERT INTO ' . $this->req_tbl .
            $this->db->sql_build_array('INSERT', $data);
        $result = $this->db->sql_query($sql);
        $this->db->sql_freeresult($result);
        return true;
    }

    public function disable_user_posting($event)
    {
        if ($event['mode']!='post') return false;
        $user_id = $this->user->data['user_id'];
        $fid = $this->request->variable('f', '');
        $request_fid = explode(',', $this->config['snp_request_fid']);
        if (!in_array($fid, $request_fid)) return false;
        $sql = 'SELECT * FROM ' . $this->req_users_tbl ." WHERE user_id=$user_id";
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        if (!$row)
        {
            $this->insert_request_user($user_id);
            $row = $this->select_request_user($user_id);
        }
        $n_left = $row['n_left'];
        $n_base = $row['n_base'];
        $n_offset = $row['n_offset'];
        if ($row['b_unlimited'])
        {
            $strn = 'You have unlimited request slots.';
            $this->template->assign_var('S_UNLIMITED_REQUEST', $strn);
        }
        else
        {
            $strn = "You have $n_left of $n_base request slots left.";
            if (!($n_left > 0))
            {
                if ($n_offset > 0)
                {
                    $strn .= "<br>One request slot will be deducted from your reserve ($n_offset remaining).";
                }
                else
                {
                    $dt = new \DateTime(date('r', $row['reset_time']));
                    $reset_time_strn = $dt->format('Y/m/d h:i a');
                    $strn = "<h2>You don't have any request slots left ($n_left of $n_base).<br>
                        You may close some of your open requests<br>or wait until your next reset period ($reset_time_strn).</h2>";
                    $open_request = $this->select_open_request($user_id);
                    foreach($open_request as $openreq)
                    {
                        $tid = $openreq['tid'];
                        $url = "/viewtopic.php?t=$tid";
                        $open_link[] = '<a href="' . $url . '">' . $url . '</a>';
                    }
                    $strn .= implode('<br>', $open_link);
                    trigger_error($strn);
                    trigger_error('error');

                }
            }
        }
        $this->template->assign_var('S_REQUEST_USER_INFO', $strn);
    }

    public function show_request_icons($event)
    {
        $data = $event['topic_data'];
        $title = $data['topic_title'];
        $tid = $event['topic_id'];
        $reqdata = $this->select_request($tid);
        // If no request entry or request was closed
        $closed = [2, 9]; // 2=solved, 9=terminated
        if (!$reqdata || in_array($reqdata['status'], $closed)) return false;
        $ctx['B_SHOW_CLOSE_BTN'] = true;
        $this->template->assign_vars($ctx);
    }

    public function get_user_string_from_usernames_sql($aUserdata, $prepend='', $bDullBlocked=false)
    {
        while ($row = array_pop($aUserdata))
        {
            $uname = $row['username'];
            $uid = $row['user_id'];
            if ($bDullBlocked && !$row['snp_enable_at_notify'])
                $color = '555588';
            else
                $color = $row['user_colour'];
            if (!$color) $color = '000000';
            $username_string = "[color=#$color][b]$prepend$uname".'[/b][/color]';
            $a_user_string[$uname] = $username_string;
        }
        return $a_user_string;
    }

    public function show_dibs($event)
    {
        $data = $event['topic_data'];
        $title = $data['topic_title'];
        $tid = $event['topic_id'];
        // $sql = 'SELECT * FROM ' . $this->dibs_tbl . ' WHERE tid=' . $tid;
        // $result = $this->db->sql_query($sql);
        // $row = $this->db->sql_fetchrow($result);
        // $this->db->sql_freeresult($result);
        // If no one called dibs, show dibs button and quit
        $req = $this->select_request($tid);
        $reqOpenStatus = [1]; // Open request has status of 1
        if (!$req) return false;
        $uid            = $req['fulfiller_uid'];
        $username       = $req['fulfiller_username'];
        $colour         = $req['fulfiller_colour'];
        $requester_uid  = $req['requester_uid'];
        $username_string = get_username_string('no_profile', $uid, $username, $colour);
        if (in_array($req['status'], $reqOpenStatus))
        {
            $this->template->assign_var('B_SHOW_DIBS_BTN', true);
        }
        elseif($req['status'] == 3)
        {
            $commit_time     = $req['commit_time'];
            $dt       = new \DateTime(date('r', $commit_time));
            $datetime = $dt->format('D M d, Y  H:i a');
            $strn     = "$username_string has offered to fulfill this request on $datetime";
            $this->template->assign_var('S_REQUEST_INFO', $strn);
            if ($this->user->data['username'] == $username)
                $this->template->assign_vars([ 'B_SHOW_FULFILL_BTN' => true, ]);
        }
        elseif($req['status'] == 4)
        {
            $fulfilled_time = $req['fulfilled_time'];
            $dt              = new \DateTime(date('r', $fulfilled_time));
            $datetime = $dt->format('D M d, Y  H:i a');
            $username_string = get_username_string('no_profile', $uid, $username, $colour);
            $strn            = "$username_string has fulfilled this request on $datetime";
            // Show fulfilled text and hide fulfill button
            $this->template->assign_var('S_REQUEST_INFO', $strn);
            // If fulfilled and user is the requester, show solve button
            if ($this->user->data['user_id'] == $requester_uid)
            {
                $this->template->assign_var('B_SHOW_SOLVE_BTN', true);
            }
        }
        elseif($req['status'] == 2)
        {
            $solved_time     = $req['solved_time'];
            $dt              = new \DateTime(date('r', $solved_time));
            $datetime        = $dt->format('D M d, Y  H:i a');
            $username_string = get_username_string('no_profile', $uid, $username, $colour);
            $strn            = "This request was solved by $username_string on $datetime";
            // Show fulfilled text and hide fulfill button
            $this->template->assign_var('S_REQUEST_INFO', $strn);
        }
        else
        {
            return false;
        }
    }

}
