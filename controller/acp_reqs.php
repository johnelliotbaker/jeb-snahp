<?php
namespace jeb\snahp\controller;
use \Symfony\Component\HttpFoundation\Response;
use \Symfony\Component\HttpFoundation\JsonResponse;
use jeb\snahp\core\base;

function prn($var) {
    if (is_array($var))
    { foreach ($var as $k => $v) { echo "... $k => "; prn($v); }
    } else { echo "$var<br>"; }
}

class acp_reqs extends base
{

    public function __construct(
    )
    {
        // $this->u_action = 'app.php/snahp/admin/';
    }

    public function handle_reset_user($username)
    {
        $this->reject_non_moderator();
        $this->tbl = $this->container->getParameter('jeb.snahp.tables');
        $this->def = $this->container->getParameter('jeb.snahp.req')['def'];
        $data = $this->recalculate_request_users($username);
        // meta_refresh(2, $this->u_action);
        // $message = 'Processed without an error.';
        $json = new JsonResponse();
        $json->setData($data);
        return $json;
    }

    public function update_deleted_requests($uid)
    {
        // Get all request entry for user
        $sql = 'SELECT tid, status FROM ' . $this->tbl['req'] ." WHERE requester_uid=$uid" . '
            AND ' . $this->db->sql_in_set('status', $this->def['set']['closed'], true);
        $result = $this->db->sql_query($sql);
        $rowset = $this->db->sql_fetchrowset($result);
        $this->db->sql_freeresult($result);
        $a_tid_req = array_map(function($a) {return $a['tid'];}, $rowset);
        // Check against topics that exists
        $sql = 'SELECT topic_id FROM ' . TOPICS_TABLE . ' WHERE ' .
            $this->db->sql_in_set('topic_id', $a_tid_req);
        $result = $this->db->sql_query($sql);
        $rowset = $this->db->sql_fetchrowset($result);
        $this->db->sql_freeresult($result);
        $a_tid = array_map(function($a) {return $a['topic_id'];}, $rowset);
        $a_diff = array_diff($a_tid_req, $a_tid);
        if (!$a_diff) return false;
        // Update request entries
        $data = [
            'b_graveyard' => 1,
            'status' => $this->def['deleted'],
        ];
        $sql = 'UPDATE ' . $this->tbl['req'] . '
            SET ' . $this->db->sql_build_array('UPDATE', $data). '
            WHERE ' . $this->db->sql_in_set('tid', $a_diff);
        $this->db->sql_query($sql);
    }

    public function recalculate_request_users($username)
    {
        $this->reject_non_moderator();
        $userdata = $this->select_user_by_username($username);
        $data = [];
        if (!$userdata)
        {
            $data = [
                'status' => 'That username does not exist.',
            ];
            return $data;
        }
        $uid = $userdata['user_id'];
        $this->update_deleted_requests($uid);
        $cdef = $this->def['set']['closed'];
        $gid = $userdata['group_id'];
        $gdata = $this->select_group($gid);
        $sql = 'SELECT tid FROM ' . $this->tbl['req'] ." WHERE requester_uid=$uid AND " .
            $this->db->sql_in_set('status', $cdef, true);
        $result = $this->db->sql_query($sql);
        $rowset = $this->db->sql_fetchrowset($result);
        $this->db->sql_freeresult($result);
        $time = time();
        $cycle_time = $this->config['snp_req_cycle_time'];
        $total = count($rowset);
        $goffset = $gdata['snp_req_n_offset'];
        $gbase = $gdata['snp_req_n_base'];
        if ($total >= $gbase)
        {
            $total -= $gbase;
            $n_use = $gbase;
            $n_offset = -$total + $goffset;
        }
        else
        {
            $n_use = $total;
            $n_offset = $goffset;
        }
        $data = [
            'n_use'            => $n_use,
            'n_use_this_cycle' => 0,
            'n_offset'         => $n_offset,
            'start_time'       => $time,
            'cycle_deltatime'  => $cycle_time,
            'reset_time'       => 0,
            'b_nolimit'        => $gdata['snp_req_b_nolimit'],
            'b_give'           => $gdata['snp_req_b_give'],
            'b_take'           => $gdata['snp_req_b_take'],
            'b_active'         => $gdata['snp_req_b_active'],
        ];
        $this->update_request_users($uid, $data);
        $data = [
            'status' => 'User statistics reset successfully for ' . $username,
            // 'status' => $userdata,
        ];
        return $data;
    }

    public function get_banned_dibbers()
    {
        $this->reject_non_moderator();
        $sql = 'SELECT user_colour, username_clean FROM ' . USERS_TABLE ." WHERE snp_req_dib_enable=0";
        $result = $this->db->sql_query($sql);
        $data = [];
        while($row = $this->db->sql_fetchrow($result))
        {
            $data[] = [
                'username_clean' => $row['username_clean'],
                'user_colour' => $row['user_colour'],
            ];
        }
        $this->db->sql_freeresult($result);
        $json = new JsonResponse();
        $json->setData($data);
        return $json;
    }

    public function get_userinfo($username)
    {
        $this->reject_non_moderator();
        if (!$username)
        {
            $userdata = [];
        }
        else
        {
            $userdata = $this->select_request_users_by_username($username);
        }
        $json = new JsonResponse();
        $json->setData($userdata);
        return $json;
    }

    public function handle_manage_user()
    {
        $this->reject_non_moderator();
        $data = $this->user->data;
        $auth = $this->auth;
        $request = $this->request;
        // if (!$auth->acl_gets('a_'))
        // {
        //     trigger_error('Lasciate ogne speranza, voi ch’intrate.');
        // }
        // Add security for CSRF
        add_form_key('jeb/snahp');
        if ($request->is_set_post('submit'))
        {
            if (!check_form_key('jeb/snahp'))
            {
                trigger_error('Form Key Error');
            }
            $usernames        = $request->variable('usernames', '');
            $user_id          = $request->variable('user_id', 0);
            $n_use            = $request->variable('n_use', 0);
            $n_use_this_cycle = $request->variable('n_use_this_cycle', 0);
            $n_offset         = $request->variable('n_offset', 1);
            $reset_time       = $request->variable('reset_time', 0);
            $status           = $request->variable('status', 1);
            $data = [
                'n_use'            => $n_use,
                'n_use_this_cycle' => $n_use_this_cycle,
                'n_offset'         => $n_offset,
                'reset_time'       => $reset_time,
                'status'           => $status,
            ];
            $this->update_request_users($user_id, $data);
            if (false)
            {
                meta_refresh(2, $this->u_action);
                $message = 'Processed without an error.';
                trigger_error($message);
            }
        }

        $usernames        = $request->variable('usernames', '');
        $user_id          = $request->variable('user_id', 0);
        $n_use            = $request->variable('n_use', 0);
        $n_use_this_cycle = $request->variable('n_use_this_cycle', 0);
        $n_offset         = $request->variable('n_offset', 1);
        $reset_time       = $request->variable('reset_time', 0);
        $status           = $request->variable('status', 1);
        $this->template->assign_vars([
            'B_ENABLE'         => true,
            'USERNAMES'        => $usernames,
            'USER_ID'          => $user_id,
            'N_USE'            => $n_use,
            'N_USE_THIS_CYCLE' => $n_use_this_cycle,
            'N_OFFSET'         => $n_offset,
            'RESET_TIME'       => $reset_time,
            'STATUS'           => $status,
        ]);
        return $this->helper->render('@jeb_snahp/mcp/mcp_snp_reset_stats.html');
    }

}
