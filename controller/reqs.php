<?php

use Symfony\Component\HttpFoundation\Response;

namespace jeb\snahp\controller;

use jeb\snahp\core\base;

function prn($var) {
    if (is_array($var))
    { foreach ($var as $k => $v) { echo "... $k => "; prn($v); }
    } else { echo "$var<br>"; }
}

class reqs extends base
{
	protected $u_action;

    public function __construct()
    {
        $this->ptn = '#\[(accepted|request|closed|fulfilled|solved)\]\s*#is';
    }

	public function handle_mcp($mode, $uid)
    {
        $this->user_id = $this->user->data['user_id'];
        $this->reject_non_moderator();
        $this->tbl = $this->container->getParameter('jeb.snahp.tables');
        $this->def = $this->container->getParameter('jeb.snahp.reqsmcp')['def'];
        $def = $this->def;
        // $this->u_action = "viewtopic.php?f=$fid&t=$tid&p=$pid";
        switch ($mode)
        {
            case $def['enable']: // Disallow user from dib
                $this->enable_dibber($uid, true);
                meta_refresh(2, '/');
                trigger_error('User is now allowed to dib.');
                break;
            case $def['disable']: // Allow user to dib
                $this->enable_dibber($uid, false);
                meta_refresh(2, '/');
                trigger_error('User is now banned from dibbing.');
                break;
        }
    }

    public function enable_dibber($uid, $b_enable=true)
    {
        $enable = $b_enable ? 1 : 0;
        $sql_arr = [
            'snp_req_dib_enable' => $enable,
        ];
        $sql = 'UPDATE ' . USERS_TABLE . ' SET
            ' . $this->db->sql_build_array('UPDATE', $sql_arr) .'
            WHERE user_id=' . $uid;
        $this->db->sql_query($sql);
    }


	public function handle($fid, $tid, $pid, $mode)
    {
        $this->user_id = $this->user->data['user_id'];
        $this->b_mod = $this->is_mod();
        $this->reject_anon();
        $this->tbl = $this->container->getParameter('jeb.snahp.tables');
        $this->def = $this->container->getParameter('jeb.snahp.req')['def'];
        $def = $this->def;
        $this->u_action = "viewtopic.php?f=$fid&t=$tid&p=$pid";
        $reqdata = $this->select_request($tid);
        if (!$reqdata)
        {
			meta_refresh(2, $this->u_action);
            trigger_error('That request does not exist.');
        }
        switch ($mode)
        {
            case $def['solve']: // To solve request
                $this->reject_invalid_users($reqdata);
                $this->solve_request($fid, $tid, $pid, $def['solve'], $reqdata);
                break;
            case $def['terminate']: // To terminate request
                $this->reject_invalid_users($reqdata);
                $this->solve_request($fid, $tid, $pid, $def['terminate'], $reqdata);
                break;
            case $def['dib']:
                $this->dib_request($fid, $tid, $pid);
                break;
            case $def['undib']:
                $this->undib_request($fid, $tid, $pid);
                break;
            case $def['fulfill']:
                $this->fulfill_request($fid, $tid, $pid);
                break;
        }
    }

    public function select_request_users($uid)
    {
        $sql = 'SELECT * FROM ' . $this->tbl['requsr'] ." WHERE user_id=$uid";
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        return $row;
    }

    public function update_request_users($uid, $data)
    {
        $sql = 'UPDATE ' . $this->tbl['requsr'] . '
            SET ' . $this->db->sql_build_array('UPDATE', $data) . '
            WHERE user_id=' . $uid;
        $result = $this->db->sql_query($sql);
        $this->db->sql_freeresult($result);
    }

    public function recalculate_request_users($uid)
    {
        $cdef = $this->def['set']['closed'];
        $gid = $this->user->data['group_id'];
        $gdata = $this->select_group($gid);
        $sql = 'SELECT count(*) as n_use FROM ' . $this->tbl['req'] ." WHERE requester_uid=$uid AND " .
            $this->db->sql_in_set('status', $cdef, true);
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        $time = time();
        $thirtydays = 60*60*24*30;
        $reset_time = $time + $thirtydays;
        $n_use = $row['n_use'];
        $new_offset = $gdata['snp_req_n_offset'] + $gdata['snp_req_n_base'] - $n_use;
        $data = [
            'n_use'           => $gdata['snp_req_n_base'],
            'n_cycle_use'     => 0,
            'n_offset'        => $new_offset,
            'start_time'      => $time,
            'cycle_deltatime' => $thirtydays,
            'reset_time'      => $reset_time,
            'b_nolimit'       => $gdata['snp_req_b_nolimit'],
            'b_give'          => $gdata['snp_req_b_give'],
            'b_take'          => $gdata['snp_req_b_take'],
            'b_active'        => $gdata['snp_req_b_active'],
        ];
        $this->update_request_users($uid, $data);
        return;
    }

    public function increment_request_users_slot($uid, $slot=1)
    {
        $ru   = $this->select_request_users($uid);
        $udata = $this->select_user($ru['user_id']);
        $gdata = $this->select_group($udata['group_id']);
        $nu   = $ru['n_use'];
        $nb   = $gdata['snp_req_n_base'];
        $no   = $ru['n_offset'];
        $nu  -= $slot;
        if ($nu > $nb)
        {
            $no -= $nu - $nb;
            $nu = $nb;
        }
        if ($nu < 0)
        {
            $no -= $nu;
            $nu = 0;
        }
        $sql = 'UPDATE ' . $this->tbl['requsr'] . "
            SET n_use=$nu, n_offset=$no " . '
            WHERE user_id=' . $uid;
        $this->db->sql_query($sql);
    }

    public function remove_request($tid)
    {
        $data = ['tid' => $tid];
        $sql = 'DELETE FROM ' . $this->tbl['req'] . $this->db->sql_build_array('DELETE', $data); 
        $result = $this->db->sql_query($sql);
        $this->db->sql_freeresult($result);
    }

    public function solve_request($fid, $tid, $pid, $new_status, $req)
    {
        // Set confirmed_time
        $def = $this->def;
        $resolvedStatus = [$def['solve'], $def['terminate']];
        if (!$req)
        {
			meta_refresh(2, $this->u_action);
			trigger_error('That request does not exist.');
		}
        if (in_array($req['status'], $resolvedStatus))
		{
			meta_refresh(2, $this->u_action);
			trigger_error('That request had already been terminated.');
		}
        $user_id = $this->user_id;
        if ($user_id != $req['requester_uid'] && !$this->auth->acl_gets('a_', 'm_'))
        {
            //TODO: Send notification to user if closed by mod
            meta_refresh(2, $this->u_action);
            trigger_error('Only the requester and the moderators are allowed to close this request.');
        }
        $time = time();
        $data = [
            'status' => $new_status,
            'solved_time' => $time,
        ];
        $this->update_request($tid, $data);
        $this->increment_request_users_slot($req['requester_uid'], 1);
        $ptn = $this->ptn;
        $repl = '';
        if ($new_status == $def['solve'])
        {
            $repl = '[Solved]';
        }
        elseif ($new_status == $def['terminate'])
        {
            $repl = '[Closed]';
        }
        $status = $this->update_topic_title($fid, $tid, $pid, $ptn, $repl);
        if ($status)
        {
            meta_refresh(2, $this->u_action);
            trigger_error($status);
        }
        meta_refresh(2, $this->u_action);
        if ($new_status == $def['solve'])
        {
            trigger_error('This request was solved.');
        }
        elseif ($new_status == $def['terminate'])
        {
            trigger_error('This request was terminated.');
        }
    }

    public function reject_invalid_users($reqdata)
    {
        $myuid = $this->user_id;
        $requester_uid = $reqdata['requester_uid'];
        if ($myuid == $requester_uid)
            return 1;
        elseif ($this->auth->acl_gets('a_', 'm_'))
            return 2;
        else
            trigger_error('Only the requester and the moderators have access to this page.');
    }

	public function fulfill_request($fid, $tid, $pid)
    {
        // Update titles on topic & posts also forum summary
        $ptn = $this->ptn;
        $repl = '[Fulfilled]';
        $status = $this->update_topic_title($fid, $tid, $pid, $ptn, $repl);
        if ($status)
        {
            meta_refresh(2, $this->u_action);
            trigger_error($status);
        }
        $data = [
            'fulfilled_time' => time(),
            'status'         => 4,
        ];
        $this->update_request($tid, $data);
        meta_refresh(2, $this->u_action);
        $message = 'Thank you for fulfilling this request!';
        trigger_error($message);
    }

	public function undib_request($fid, $tid, $pid)
    {
        $dibdata = $this->select_dibs($tid);
        $def = $this->def;
        if (!$dibdata || !$dibdata['status']==$def['dib'])
            trigger_error('No open dibs found.');
        $b_dibber = $this->is_dibber($dibdata);
        $b_mod = $this->is_mod();
        if (!$b_dibber && !$b_mod)
            trigger_error('Only dibber or moderators may undib.');
        // Update titles on topic & posts also forum summary
        $ptn = $this->ptn;
        $repl = '[Request]';
        $status = $this->update_topic_title($fid, $tid, $pid, $ptn, $repl);
        if ($status)
        {
            meta_refresh(2, $this->u_action);
            trigger_error($status);
        }
        $time = time();
        $data = [
            'fulfiller_uid'      => null,
            'fulfiller_username' => '',
            'fulfiller_colour'   => '',
            'commit_time'        => null,
            'status' => $this->def['open'],
        ];
        $this->update_request($tid, $data);
        $data = [
            'undib_time' => $time,
            'undibber_uid' => $this->user->data['user_id'],
            'status' => $this->def['undib'],
        ];
        $this->update_dibs($tid, $data);
        meta_refresh(2, $this->u_action);
        $message = 'You have undibbed this request.';
        trigger_error($message);
    }

	public function dib_request($fid, $tid, $pid)
    {
        if (!$this->user->data['snp_req_dib_enable'])
        {
            meta_refresh(2, $this->u_action);
            trigger_error('You are not allowed to dib requests.');
        }
        $dibdata = $this->select_dibs($tid);
        $def = $this->def;
        $time = time();
        $undib_cooldown = $this->config['snp_req_redib_cooldown_time'];
        if ($dibdata)
        {
            if ($dibdata['status'] == $def['dib'])
            {
                trigger_error('Someone already dibbed this request.');
            }
            else
            {
                // Must have been undibbed
                $b_dibber = $this->is_dibber($dibdata);
                if ($b_dibber && !$this->b_mod)
                {
                    $redib_time = $dibdata['undib_time'] + $undib_cooldown;
                    if ($time < $redib_time)
                    {
                        trigger_error('Cannot dib so soon after undibbing a request.<br>
                            You can dib this request again on ' . $this->get_dt($redib_time));
                    }
                }
            }
        }
        // Update titles on topic & posts also forum summary
        $ptn = $this->ptn;
        $repl = '[Accepted]';
        $status = $this->update_topic_title($fid, $tid, $pid, $ptn, $repl);
        if ($status)
        {
            meta_refresh(2, $this->u_action);
            trigger_error($status);
        }
        $data = [
            'fulfiller_uid'      => $this->user->data['user_id'],
            'fulfiller_username' => $this->user->data['username'],
            'fulfiller_colour'   => $this->user->data['user_colour'],
            'commit_time'        => time(),
            'status' => $this->def['dib'],
        ];
        $this->update_request($tid, $data);
        $data = [
            'tid' => $tid,
            'dibber_uid' => $this->user->data['user_id'],
            'dib_time' => $time,
            'status' => $this->def['dib'],
        ];
        $this->insert_dibs($data);
        meta_refresh(2, $this->u_action);
        $message = 'You called dibs on a request! Thanks a lot for your help.';
        trigger_error($message);
    }

    public function is_dibber($dibdata)
    {
        return $this->user_id == $dibdata['dibber_uid'];

    }

    public function is_requester($reqdata)
    {
        return $this->user_id = $reqdata['requester_uid'];
    }

}
