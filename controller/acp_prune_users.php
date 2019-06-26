<?php

namespace jeb\snahp\controller;

use jeb\snahp\core\base;

class acp_prune_users extends base
{

    protected $base_url = '';

    public function __construct()/*{{{*/
    {
    }/*}}}*/

	public function handle($mode)/*{{{*/
	{
        $this->reject_non_admin('Error Code: 10d3683c22');
        $this->tbl = $this->container->getParameter('jeb.snahp.tables');
        switch ($mode)
        {
        case 'activate':
            $cfg['tpl_name'] = '';
            $cfg['b_feedback'] = false;
            $cfg['state'] = 'activate';
            return $this->deactivate($cfg);
            break;
        case 'deactivate':
            $cfg['tpl_name'] = '';
            $cfg['b_feedback'] = false;
            $cfg['state'] = 'deactivate';
            return $this->deactivate($cfg);
            break;
        case 'prune':
            $cfg['tpl_name'] = '';
            $cfg['b_feedback'] = false;
            return $this->prune($cfg);
            break;
        default:
            trigger_error('Invalid mode. Error Code: 5176d454eb');
            break;
        }
	}/*}}}*/

    public function deactivate($cfg)/*{{{*/
    {
        include_once('includes/acp/acp_prune.php');
        include_once('includes/functions_user.php');
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        if ($cfg['state'] == 'activate')
        {
            $set_user_to = USER_NORMAL;
        }
        else
        {
            $set_user_to = USER_INACTIVE;
        }
        // Get options
        $time_strn = $this->request->variable('time', '');
        $required_posts = $this->request->variable('required_posts', 0);
        $group = $this->request->variable('group', 0);
        $doa = $this->request->variable('doa', 1);
        $verbose = $this->request->variable('verbose', 0);
        $n_users = $this->request->variable('n_users', 0);
        if ($time_strn)
        {
            $time_before = strtotime($time_strn);
        }
        else
        {
            $time_before = 0;
        }
        $sql = 'SELECT user_id, username, user_type, user_posts, group_id, user_lastvisit FROM ' . USERS_TABLE;
        $result = $this->db->sql_query($sql);
        $time_start = microtime(true);
        $time_loop = microtime(true);
        $i = 0;
        $n_process = 0;
        $row = 1;
        while($row)
        {
            $row = $this->db->sql_fetchrow($result);
            // prn($row['post_id'], true);
            // prn(PHP_EOL, true);
            // Get user statistics
            $i += 1;
            $user_id = $row['user_id'];
            $user_type = $row['user_type'];
            $n_posts = $row['user_posts'];
            $group_id = $row['group_id'];
            $user_lastvisit = $row['user_lastvisit'];
            // If time before is specified
            if ($time_before && $user_lastvisit > $time_before)
            {
                continue;
            }
            // Rejections
            if (!in_array($user_type, [USER_NORMAL, USER_INACTIVE]))
            {
                // If special user (admin or bots) continue
                continue;
            }
            // doa means signed up but never logged in. $user_lastvisit=0
            if ($doa && $user_lastvisit > 0)
            {
                continue;
            }
            // If minimum posts are specified
            if ($required_posts && $n_posts > $required_posts)
            {
                continue;
            }
            if ($group && $group_id != $group)
            {
                continue;
            }
            $tt = microtime(true);
            $this->user->reset_login_keys($user_id);
            $tt = microtime(true) - $tt;
            $this->send_message(['sql_fetch' => $tt]);
            $data = [
                'user_type' => $set_user_to,
                'user_inactive_time' => time(),
                'user_inactive_reason' => INACTIVE_MANUAL,
            ];

            $tt = microtime(true);
            $sqlw = 'UPDATE ' . USERS_TABLE . ' SET ' . $this->db->sql_build_array('UPDATE', $data) . " WHERE user_id={$user_id}";
            $this->db->sql_query($sqlw);
            $tt = microtime(true) - $tt;
            $this->send_message(['tt' => $tt]);
            if ($verbose)
            {
                $this->send_message($row);
            }
            if ($i > 200000)
            {
                break;
            }
            if ($i % 100 == 0)
            {
                $time_loop = microtime(true) - $time_loop;
                $this->send_message(['i'=>"{$i}", 'time' => "{$time_loop}"]);
                $time_loop = microtime(true);
            }
            $n_process += 1;
            if ($n_users && $n_process > $n_users)
            {
                $i -= 1;
                $n_process -= 1;
                break;
            }
        }
        $this->db->sql_freeresult($result);
        $time_end = microtime(true) - $time_start;
        $this->send_message(['Processed' => "{$i}", 'Modified'=>"{$n_process}", 'Total Time' => "{$time_end}"]);
        $js = new \phpbb\json_response();
        $js->send();
    }/*}}}*/

    public function prune($cfg)/*{{{*/
    {
        include_once('includes/acp/acp_prune.php');
        include_once('includes/functions_user.php');
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        $time_strn = $this->request->variable('time', '0');
        $limit = $this->request->variable('limit', 500);
        $process_doa = $this->request->variable('doa', 0);
        $b_verbose = $this->request->variable('verbose', 0);
        $n_loop = $this->request->variable('n_loop', 0);
        $b_count = $this->request->variable('count_only', 0);
        if (!$time_strn)
        {
            trigger_error('You must specify cut off date e.g. ?time=2018-10-30');
        }
        $time = strtotime($time_strn);
        // Prune users that never signed in after creation
        if ($process_doa==1)
        {
            $cfg['limit'] = $limit;
            $cfg['time'] = $time;
            $cfg['detail'] = 'Prune users that never signed in after user creation';
            $cfg['process_doa'] = true;
            $cfg['b_verbose'] = $b_verbose;
            $cfg['n_loop'] = $n_loop;
            $cfg['b_count'] = $b_count;
            if ($b_count)
            {
                $count = $this->get_users_inactive_after_count($cfg);
                $this->send_message(['Total'=>"{$count}"]);
            }
            else
            {
                $this->prune_users($cfg);
            }
        }
        // Prune users that are inactive
        else
        {
            $cfg['limit'] = $limit;
            $cfg['time'] = $time;
            $cfg['detail'] = "Prune users that did not login after {$time_strn}";
            $cfg['process_doa'] = false;
            $cfg['b_verbose'] = $b_verbose;
            $cfg['n_loop'] = $n_loop;
            $cfg['b_count'] = $b_count;
            if ($b_count)
            {
                $count = $this->get_users_inactive_after_count($cfg);
                $this->send_message(['Total'=>"{$count}"]);
            }
            else
            {
                $this->prune_users($cfg);
            }
        }
        // Kill the stream
        $js = new \phpbb\json_response();
        $js->send();
        // meta_refresh(2, $this->base_url);
        // trigger_error('You are now subscribed to this topic.');
    }/*}}}*/

    public function prune_users($cfg)/*{{{*/
    {
        $b_verbose = $cfg['b_verbose'];
        $n_loop = $cfg['n_loop'];
        $action = 'deactivate';
        $this->send_message(['status'=>'START',  'detail' => $cfg['detail'], 'i' => 0]);
        $i_loop = 0;
        while($user_ids = $this->get_users_inactive_after($cfg))
        {
            if ($n_loop > 0 && $i_loop >= $n_loop)
            {
                $this->send_message(['status'=>'BREKAING due to n_loop', 'i' => $cfg['limit']*($i_loop+1)]);
                break;
            }
            if (count($user_ids))
            {
                if ($action == 'deactivate')
                {
                    user_active_flip('deactivate', $user_ids);
                    $l_log = 'LOG_PRUNE_USER_DEAC';
                }
                else if ($action == 'delete')
                {
                    if ($deleteposts)
                    {
                        user_delete('remove', $user_ids);
                        $l_log = 'LOG_PRUNE_USER_DEL_DEL';
                    }
                    else
                    {
                        user_delete('retain', $user_ids, true);
                        $l_log = 'LOG_PRUNE_USER_DEL_ANON';
                    }
                }
                // $phpbb_log->add('admin', $user->data['user_id'], $user->ip, $l_log, false, array(implode(', ', $usernames)));
                // $msg = $user->lang['USER_' . strtoupper($action) . '_SUCCESS'];
            }
            $this->send_message(['status'=>'PROGRESS', 'i' => $cfg['limit']*($i_loop+1)]);
            if ($b_verbose)
            {
                $this->send_message(['user_ids' => $user_ids]);
            }
            $i_loop += 1;
        }
        $this->send_message(['status'=>'SUCCESS']);
    }/*}}}*/

    private function get_users_inactive_after_count($cfg)/*{{{*/
    {
        $time = $cfg['time'];
        $limit = $cfg['limit'];
        $process_doa = array_key_exists('process_doa', $cfg) && $cfg['process_doa'];
        $enum_user_normal = USER_NORMAL;
        if ($process_doa)
        {
            $where = "u.user_type={$enum_user_normal} AND u.user_lastvisit = 0";
        }
        else
        {
            $where = "u.user_type={$enum_user_normal} AND u.user_lastvisit > 0 AND u.user_lastvisit < {$time}";
        }
        $sql_ary = [
            'SELECT' => 'COUNT(*) as count',
            'FROM' => [USERS_TABLE => 'u'],
            'WHERE' => $where,
        ];
        $sql = $this->db->sql_build_query('SELECT', $sql_ary);
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        if ($row)
        {
            return $row['count'];
        }
        return 0;
    }

    private function get_users_inactive_after($cfg)/*{{{*/
    {
        $time = $cfg['time'];
        $limit = $cfg['limit'];
        $process_doa = array_key_exists('process_doa', $cfg) && $cfg['process_doa'];
        $enum_user_normal = USER_NORMAL;
        if ($process_doa)
        {
            $where = "u.user_type={$enum_user_normal} AND u.user_lastvisit = 0";
        }
        else
        {
            $where = "u.user_type={$enum_user_normal} AND u.user_lastvisit > 0 AND u.user_lastvisit < {$time}";
        }
        $sql_ary = [
            'SELECT' => 'u.user_id',
            'FROM' => [USERS_TABLE => 'u'],
            'WHERE' => $where,
        ];
        $sql = $this->db->sql_build_query('SELECT', $sql_ary);
        $result = $this->db->sql_query_limit($sql, $limit);
        $rowset = $this->db->sql_fetchrowset($result);
        $this->db->sql_freeresult($result);
        $data = [];
        foreach ($rowset as $row)
        {
            $data[] = $row['user_id'];
        }
        return $data;
    }/*}}}*/

    public function send_message($data) {/*{{{*/
        echo "data: " . json_encode($data) . PHP_EOL;
        echo PHP_EOL;
        ob_flush();
        flush();
    }/*}}}*/

}
