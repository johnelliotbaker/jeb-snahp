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

    public function prune($cfg)/*{{{*/
    {
        include_once('includes/acp/acp_prune.php');
        include_once('includes/functions_user.php');
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        $time_strn = $this->request->variable('time', '0');
        $limit = $this->request->variable('limit', 500);
        $process_doa = $this->request->variable('doa', 0);
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
            $this->prune_users($cfg);
        }
        // Prune users that are inactive
        else
        {
            $cfg['limit'] = $limit;
            $cfg['time'] = $time;
            $cfg['detail'] = "Prune users that did not login after {$time_strn}";
            $cfg['process_doa'] = false;
            $this->prune_users($cfg);
        }
        // Kill the stream
        $js = new \phpbb\json_response();
        $js->send();
        // meta_refresh(2, $this->base_url);
        // trigger_error('You are now subscribed to this topic.');
    }/*}}}*/

    public function prune_users($cfg)/*{{{*/
    {
        $action = 'deactivate';
        $this->send_message(['status'=>'START',  'detail' => $cfg['detail'], 'i' => 0]);
        $i_loop = 0;
        while($user_ids = $this->get_users_inactive_after($cfg))
        {
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
            $i_loop += 1;
        }
        $this->send_message(['status'=>'SUCCESS']);
    }/*}}}*/

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
