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

    public function handle($mode)
    {
        switch ($mode)
        {
        case 'test':
            $cfg = [];
            return $this->test($cfg);
        case 'do_rurs':
            $cfg = [];
            return $this->resynch_user_requests_solved($cfg);
        case 'rurs':
            // resynchronize_user_requests_solved
            $this->reject_non_admin();
            $cfg = [];
            return $this->handle_rurs($cfg);
        default:
            break;
        }
        trigger_error('You must specify valid mode.');
    }

    public function test($cfg)
    {
        $this->reject_non_admin();
        return $this->helper->render('@jeb_snahp/acp/resynch_user_requests_solved.html');
    }

    public function send_message($data) {
        echo "data: " . json_encode($data) . PHP_EOL;
        echo PHP_EOL;
        ob_flush();
        flush();
    }

    public function resynch_user_requests_solved($cfg)
    {
        $this->reject_non_admin();
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        $sql = 'UPDATE ' . USERS_TABLE . ' SET snp_req_n_solve=0';
        $tbl = $this->container->getParameter('jeb.snahp.tables');
        $def = $this->container->getParameter('jeb.snahp.req')['def'];
        // Clearn all snp_req_n_solve
        $sql = 'UPDATE ' . USERS_TABLE . '
            SET snp_req_n_solve=0';
        $this->db->sql_query($sql);
        // Get total to divide transactions
        $sql = 'SELECT count(*) as total FROM ' . $tbl['req'] . '
            WHERE status=' . $def['solve'];
        $result = $this->db->sql_query_limit($sql, 1);
        $total = $this->db->sql_fetchrow($result)['total'];
        $this->db->sql_freeresult($result);
        // Transaction
        $start = 0;
        $limit = 100;
        while ($start < $total)
        {
            $this->db->sql_return_on_error(true);
            try
            {
                $this->db->sql_transaction('begin');
                $sql = 'SELECT fulfiller_uid, status FROM ' . $tbl['req'] . '
                    WHERE status=' . $def['solve'];
                $data = [
                    'status' => 'PROGRESS', 'i' => $start, 'n' => $total,
                    'message' => "$start of $total",
                    'error_message' => 'No Errors',
                    'sqlmsg' => $sql,
                ];
                $this->send_message($data);
                $result = $this->db->sql_query_limit($sql, $limit, $start);
                $data = [];
                while ($row = $this->db->sql_fetchrow($result))
                {
                    $user_id = $row['fulfiller_uid'];
                    if ($user_id)
                    {
                        if (!array_key_exists($user_id, $data))
                        {
                            $data[$user_id] = 0;
                        }
                        $data[$user_id] += 1;
                    }
                }
                $this->db->sql_freeresult($result);
                $start += $limit;
                foreach($data as $user_id => $entry)
                {
                    $dbdata = [ 'snp_req_n_solve' => "snp_req_n_solve+$entry" ];
                    $sql = 'UPDATE ' . USERS_TABLE . '
                        SET snp_req_n_solve=snp_req_n_solve+' . $entry . '
                        WHERE user_id=' . $user_id;
                    $this->db->sql_query($sql);
                }
                if ($this->db->get_sql_error_triggered())
                {
                    throw new \Exception();
                }
                $this->db->sql_transaction('commit');
            }
            catch (\Exception $e)
            {
                $this->db->sql_transaction('rollback');
                $this->db->sql_transaction('commit');
                $err_strn = '';
                $error_query = $this->db->get_sql_error_sql();
                $error_msg = implode('\n', $this->db->get_sql_error_returned());
                $err = $error_query . PHP_EOL;
                $err .= $error_msg . PHP_EOL;
                $err .= $e->getMessage() . PHP_EOL;
                $data = [
                    'status' => 'ERROR',
                    'Exception' => $e->getMessage(),
                    'error_message' => $err,
                    'i' => $start, 'n' => $total, ];
                $this->send_message($data);
                trigger_error($err);
            }
            $this->db->sql_return_on_error(false);
            // usleep(5*100000); // Simulate delay
        }
        $data = [
            'status' => 'COMPLETE', 'i' => $start, 'n' => $total,
            'error_message' => 'No Errors',
            'message' => "$total of $total",
        ];
        $this->send_message($data);
        $js = JsonResponse($data);
        return $js;
    }

    public function handle_rurs($cfg)
    {
        $this->reject_non_admin();
        return $this->helper->render('@jeb_snahp/acp/resynch_user_requests_solved.html');
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

    public function manage_requests()
    {
        if (!$this->is_admin())
        {
            trigger_error('Only available to site administrators.');
        }
        return $this->helper->render('@jeb_snahp/acp/resynch_requests.html');
    }

    public function generate_statistics()
    {
        if (!$this->is_admin())
        {
            trigger_error('Only available to site administrators.');
        }
        $time = (float)microtime();
        $tbl = $this->container->getParameter('jeb.snahp.tables');
        $def = $this->container->getParameter('jeb.snahp.req')['def'];
        // Total Requests
        $sql = 'SELECT count(*) as total from ' . $tbl['req'];
        $result = $this->db->sql_query($sql);
        $reqdata = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        $data['Total_Requests'] = $reqdata['total'];
        // Total Graveyard
        $sql = 'SELECT count(*) as total from ' . $tbl['req'] . '
            WHERE b_graveyard=1';
        $result = $this->db->sql_query($sql);
        $reqdata = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        $data['Total_Graveyard'] = $reqdata['total'];
        // Total Solved
        $sql = 'SELECT count(*) as total from ' . $tbl['req'] . '
            WHERE status=2';
        $result = $this->db->sql_query($sql);
        $reqdata = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        $data['Total_Solved'] = $reqdata['total'];
        // Total Deleted
        $sql = 'SELECT count(*) as total from ' . $tbl['req'] . '
            WHERE status=19';
        $result = $this->db->sql_query($sql);
        $reqdata = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        $data['Total_Deleted'] = $reqdata['total'];
        $time_spent = (float)microtime() - $time;
        $time_spent = sprintf('%f', $time_spent);
        $res = $this->resynchronize_requests($b_simulate=true);
        $data += $res;
        $data['Total_Query_Time'] = "$time_spent seconds";
        $json = new JsonResponse();
        $json->setData($data);
        return $json;
    }

    public function resynchronize_requests($b_simulate=false)
    {
        $this->reject_non_moderator();
        $tbl = $this->container->getParameter('jeb.snahp.tables');
        $def = $this->container->getParameter('jeb.snahp.req')['def'];
        $sql = 'SELECT * from ' . $tbl['req'];
        $result = $this->db->sql_query($sql);
        $graveyard_fid = unserialize($this->config['snp_cron_graveyard_fid'])['default'];
        $res = [];
        if ($b_simulate)
        {
            $res['should_set_deleted'] = 0;
            $res['should_set_graveyard'] = 0;
            $res['should_unset_graveyard'] = 0;
        }
        while ($reqdata = $this->db->sql_fetchrow($result))
        {
            $status = $reqdata['status'];
            $tid = $reqdata['tid'];
            $fid = $reqdata['fid'];
            $topicdata = $this->select_topic($tid);
            // If topic is deleted, update request entry
            if (!$topicdata)
            {
                if ($reqdata['b_graveyard']!=1 || $reqdata['status']!=19)
                {
                    if ($b_simulate)
                    {
                        $res['should_set_deleted'] += 1;
                    }
                    else
                    {
                        $this->update_request($tid, [
                            'b_graveyard' => 1,
                            'status' => 19,
                        ]);
                        $res[] = "$tid : (g1, s19)";
                    }
                }
                continue;
            }
            // If topic exists
            // If topic is not in graveyard
            $fid_real = $topicdata['forum_id'];
            $data = [];
            if ($fid_real != $graveyard_fid)
            {
                // && if b_graveyard is set
                if ($reqdata['b_graveyard'])
                {
                    $data = ['b_graveyard' => 0];
                    if ($b_simulate)
                    {
                        $res['should_unset_graveyard'] += 1;
                    }
                    else
                    {
                        $res[] = "$tid : (g0)";
                    }
                }
            }
            // If topic is in graveyard
            else
            {
                // && b_graveyard is not set
                if (!$reqdata['b_graveyard'])
                {
                    $data = ['b_graveyard' => 1];
                    if ($b_simulate)
                    {
                        $res['should_set_graveyard'] += 1;
                    }
                    else
                    {
                        $res[] = "$tid : (g1)";
                    }
                }
            }
            if ($data && !$b_simulate)
            {
                $this->update_request($tid, $data);
            }
        }
        $this->db->sql_freeresult($result);
        if ($b_simulate)
        {
            return $res;
        }
        $json = new JsonResponse();
        $json->setData(['Status' => 'Success']);
        return $json;
        // $res = implode('<br>', $res);
        // trigger_error($res);
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

}
