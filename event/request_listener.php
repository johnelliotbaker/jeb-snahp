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


use jeb\snahp\core\base;
use phpbb\auth\auth;
use phpbb\template\template;
use phpbb\config\config;
use phpbb\request\request_interface;
use phpbb\db\driver\driver_interface;
use phpbb\notification\manager;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use jeb\snahp\controller\reqs;


/**
 * snahp Event listener.
 */
class request_listener extends base implements EventSubscriberInterface
{
    protected $sql_limit;
    protected $notification_limit;
    protected $at_prefix;
    protected $req_tbl;
    protected $req_users_tbl;
    protected $dibs_tbl;


    public function __construct($table_prefix)
    {
        $this->table_prefix       = $table_prefix;
        $this->sql_limit          = 10;
        $this->notification_limit = 10;
        $this->req_tbl            = $table_prefix . 'snahp_request';
        $this->req_users_tbl      = $table_prefix . 'snahp_request_users';
        $this->dibs_tbl           = $table_prefix . 'snahp_dibs';
        $this->dt_ptn             = 'D M d, Y  g:i a';
    }

    static public function getSubscribedEvents()
    {
        return array(
            'core.viewtopic_assign_template_vars_before'  => array(
                array('show_request_icons', 1),
                array('show_dibs',0),
            ),
            'core.posting_modify_template_vars'     => array(
                array('disable_user_posting', 0),
            ),
            'core.posting_modify_submit_post_after' => 'create_request',
            'core.viewforum_modify_topics_data' => 'modify_request_tags',
        );
    }

    public function modify_request_tags($event)
    {
        $rowset = $event['rowset'];
        if (!$rowset) return false;
        foreach($rowset as $key => $row)
        {
            $tt = $row['topic_title'];
            $row['topic_title'] = $this->add_tag($tt, 'open');
            $rowset[$key] = $row;
        }
        $event['rowset'] = $rowset;
    }

    public function select_open_request($uid)
    {
        $sql = 'SELECT * FROM ' . $this->req_tbl . 
            " WHERE requester_uid=$uid AND " .
            $this->db->sql_in_set('status', [2, 9], true);
        $result = $this->db->sql_query($sql);
        $data = [];
        while($row = $this->db->sql_fetchrow($result))
        {
            $data[] = $row;
        }
        $this->db->sql_freeresult($result);
        return $data;
    }

    public function insert_request_users($user_id, $b_return=false)
    {
        $gid = $this->user->data['group_id'];
        $gd = $this->select_group($gid);
        $data = [
            'user_id'   => $user_id,
            'n_use'     => 0,
            'n_offset'  => $gd['snp_req_n_offset'],
            'b_nolimit' => $gd['snp_req_b_nolimit'],
            'b_give'    => $gd['snp_req_b_give'],
            'b_take'    => $gd['snp_req_b_take'],
            'b_active'  => $gd['snp_req_b_active'],
        ];
        $sql = 'INSERT INTO ' . $this->req_users_tbl . $this->db->sql_build_array('INSERT', $data); 
        $result = $this->db->sql_query($sql);
        $this->db->sql_freeresult($result);
        if ($b_return)
        {
            return $this->select_request_users($user_id);
        }
        return [];
    }

    public function reject_cycle($reqdata, $gdata)
    {
        if ($this->auth->acl_gets('a_', 'm_')) return false;
        if ($reqdata['b_nolimit']) return false;
        $user_id = $this->user->data['user_id'];
        $time = time();
        $reset_time = $reqdata['reset_time'];
        if ($time < $reset_time)
        {
            $n_use_per_cycle = $gdata['snp_req_n_cycle'];
            $n_use_this_cycle = $reqdata['n_use_this_cycle'];
            if ($n_use_this_cycle >= $n_use_per_cycle)
            {
                $reset_time_strn = $this->user->format_date($reset_time);
                $a_strn = [
                    'You cannot make more than ' . $n_use_per_cycle . ' requests per request cycle.',
                    'Your next cycle begins on ' . $reset_time_strn,
                ];
                $strn = implode('<br>', $a_strn);
                trigger_error($strn);
            }
        }
        else
        {
            $cycle_time = $this->config['snp_req_cycle_time'];
            $data = [
                'reset_time' => $time + $cycle_time,
                'n_use_this_cycle' => 0,
            ];
            $this->update_request_users($user_id, $data);
        }

    }

    public function reject_request_disabled($reqdata, $gdata)
    {
        if ($reqdata['b_active_override'])
        {
            // override has top priority
            if (!$reqdata['b_active'])
            {
                trigger_error('You are banned from making requests.');
            }
        }
        else
        {
            if (!$gdata['snp_req_b_active'])
            {
                // Group active state takes precedence unless overridden
                $groupname = $gdata['group_name'];
                trigger_error("$groupname cannot make requests.");
            }
        }

    }

    public function create_request($event)
    {
        if (!$this->config['snp_b_request'])
            return false;
        if ($event['mode']!='post') return false;
        $fid = $this->request->variable('f', '');
        $request_fid = explode(',', $this->config['snp_req_fid']);
        if (!in_array($fid, $request_fid)) return false;
        $user_id = $this->user->data['user_id'];
        $gid = $this->user->data['group_id'];
        $gdata = $this->select_group($gid);
        if (!$reqdata = $this->select_request_users($user_id))
            $reqdata = $this->insert_request_users($user_id, true);
        // Check if has unlimited requests
        $this->reject_request_disabled($reqdata, $gdata);
        $this->reject_cycle($reqdata, $gdata);
        if ($reqdata['b_nolimit'] || $gdata['snp_req_b_nolimit']) {}
        else
        {
            if ($reqdata['n_use'] < $gdata['snp_req_n_base'])
                $reqdata['n_use'] += 1; 
            else
                $reqdata['n_offset'] -= 1;
            $data['n_use'] = $reqdata['n_use'];
            $data['n_offset'] = $reqdata['n_offset'];
            $data['n_use_this_cycle'] = $reqdata['n_use_this_cycle'] + 1;
            $this->update_request_users($user_id, $data);
        }
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
        // Tag the title
        $fid = $event['data']['forum_id'];
        $tid = $event['data']['topic_id'];
        $pid = $event['data']['post_id'];
        $ptn = '#\[(accepted|request|closed|fulfilled|solved)\]\s*#is';
        $ptn = '#(\(|\[|\{)(accepted|request|closed|fulfilled|solved)(\)|\]|\})\s*#is';
        $repl = '[Request]';
        $status = $this->update_topic_title($fid, $tid, $pid, $ptn, $repl);
        return true;
    }

    public function disable_user_posting($event)
    {
        if (!$this->config['snp_b_request'])
            return false;
        if ($event['mode']!='post') return false;
        $fid = $this->request->variable('f', '');
        $request_fid = explode(',', $this->config['snp_req_fid']);
        if (!in_array($fid, $request_fid)) return false;
        $user_id = $this->user->data['user_id'];
        $gid = $this->user->data['group_id'];
        $gdata = $this->select_group($gid);
        if (!$reqdata = $this->select_request_users($user_id))
            $reqdata = $this->insert_request_users($user_id, true);
        $this->reject_request_disabled($reqdata, $gdata);
        $this->reject_cycle($reqdata, $gdata);
        $n_use = $reqdata['n_use'];
        $n_base = $gdata['snp_req_n_base'];
        $n_left = $n_base - $n_use;
        $n_offset = $reqdata['n_offset'];
        if ($reqdata['b_nolimit'] || $gdata['snp_req_b_nolimit'])
        {
            $strn = 'You have unlimited request slots.';
            $this->template->assign_var('S_UNLIMITED_REQUEST', $strn);
        }
        else
        {
            $strn = "Available request slots: $n_left of $n_base";
            if (!($n_left > 0))
            {
                if ($n_offset > 0)
                {
                    $strn .= "<br>One request slot will be deducted from your reserve ($n_offset remaining).";
                }
                else
                {
                    $reset_time_strn = $this->user->format_date($reqdata['reset_time']);
                    $strn = "<h2>You don't have any request slots left ($n_left of $n_base).<br>
                        You may close some of your open requests to reclaim your slots.</h2>";
                    // or wait until your next reset period ($reset_time_strn).</h2>";
                    $open_request = $this->select_open_request($user_id);
                    $open_link = [];
                    foreach($open_request as $openreq)
                    {
                        $tid = $openreq['tid'];
                        $url = "/viewtopic.php?t=$tid";
                        $open_link[] = '<a href="' . $url . '">' . $url . '</a>';
                    }
                    // TODO: For new user, it shows error
                    $strn .= implode('<br>', $open_link);
                    trigger_error($strn);
                }
            }
        }
        $this->template->assign_var('S_REQUEST_USER_INFO', $strn);
    }

    public function show_request_icons($event)
    {
        if (!$this->config['snp_b_request'])
            return false;
        $data = $event['topic_data'];
        $title = $data['topic_title'];
        $tid = $event['topic_id'];
        $reqdata = $this->select_request($tid);
        if (!$reqdata) return false;
        $closed = [2, 9]; // 2=solved, 9=terminated
        $b_open = !in_array($reqdata['status'], $closed);
        $b_op = $this->user->data['user_id'] == $reqdata['requester_uid'];
        $b_fulfilled = $reqdata['status'] == 4; // fulfilled id = 4
        $b_mod = $this->auth->acl_gets('a_', 'm_');
        $ctx = [];
        if (!$b_op && !$b_mod)
            return false;
        else
        {
            if ($b_open)
                $ctx['B_SHOW_CLOSE_BTN'] = true;
            if ($b_fulfilled)
                $ctx['B_SHOW_SOLVE_BTN'] = true;
        }
        $this->template->assign_vars($ctx);
    }

    public function show_dibs($event)
    {
        if (!$this->config['snp_b_request'])
            return false;
        $def = $this->container->getParameter('jeb.snahp.req')['def'];
        $data = $event['topic_data'];
        $title = $data['topic_title'];
        $tid = $event['topic_id'];
        $req = $this->select_request($tid);
        $reqOpenStatus = [$def['open']]; // Open request has status of 1
        if (!$req) return false;
        $dibdata = $this->select_dibs($tid);
        $b_dibber = $this->user->data['user_id'] == $dibdata['dibber_uid'];
        $b_mod = $this->auth->acl_gets('a_', 'm_');
        $b_dibbed = $req['status'] == $def['dib'];
        if ($b_dibbed && ($b_dibber || $b_mod))
        {
            $this->template->assign_var('B_SHOW_UNDIB_BTN', true);
        }
        $uid            = $req['fulfiller_uid'];
        $username       = $req['fulfiller_username'];
        $colour         = $req['fulfiller_colour'];
        $requester_uid  = $req['requester_uid'];
        $username_string = get_username_string('no_profile', $uid, $username, $colour);
        if (in_array($req['status'], $reqOpenStatus))
        {
            $this->template->assign_var('B_SHOW_DIBS_BTN', true);
        }
        elseif($req['status'] == 4)
        {
            $fulfilled_time = $req['fulfilled_time'];
            $datetime = $this->user->format_date($fulfilled_time);
            $username_string = get_username_string('no_profile', $uid, $username, $colour);
            $strn            = "$username_string has fulfilled this request on $datetime";
            $this->template->assign_var('S_REQUEST_INFO', $strn);
        }
        elseif($req['status'] == 3)
        {
            $commit_time     = $req['commit_time'];
            $datetime = $this->user->format_date($commit_time);
            $strn     = "$username_string has offered to fulfill this request on $datetime";
            $this->template->assign_var('S_REQUEST_INFO', $strn);
            if ($this->user->data['username'] == $username)
                $this->template->assign_vars([ 'B_SHOW_FULFILL_BTN' => true, ]);
        }
        elseif($req['status'] == 2)
        {
            $solved_time     = $req['solved_time'];
            $datetime        = $this->user->format_date($solved_time);
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
