<?php
namespace jeb\snahp\controller;

use jeb\snahp\core\base;
use \Symfony\Component\HttpFoundation\Response;
use \Symfony\Component\HttpFoundation\JsonResponse;

function prn($var) {
    if (is_array($var))
    { foreach ($var as $k => $v) { echo "... $k => "; prn($v); }
    } else { echo "$var<br>"; }
}

class acp_invite extends base
{
    protected $table_prefix;
    public function __construct($table_prefix)
    {
        $this->table_prefix = $table_prefix;
    }

    public function handle($mode)
    {
        $this->reject_non_admin();
        $this->invite_helper = new \jeb\snahp\core\invite_helper($this->container, $this->user, $this->auth, $this->request, $this->db, $this->config, $this->helper, $this->template);
        switch ($mode)
        {
        case 'test':
            $cfg = [];
            return $this->test($cfg);
        case 'giveaway_invtes':
            $cfg = [];
            return $this->giveaway_invtes($cfg);
        case 'delete_valid':
            $cfg = [];
            return $this->delete_valid($cfg);
        case 'handle_giveaways':
            $cfg = [];
            $cfg['tpl_name'] = '@jeb_snahp/acp_invite/handle_giveaways.html';
            $cfg['title'] = 'Snahp Invitation Giveaways';
            return $this->handle_giveaways($cfg);
        case 'insert_invite_users_by_group':
            $cfg = [];
            return $this->insert_invite_users_by_group($cfg);
        default:
            break;
        }
        trigger_error('You must specify valid mode.');
    }

    public function handle_giveaways($cfg)
    {
        $this->reject_anon();
        $tpl_name = $cfg['tpl_name'];
        if ($tpl_name)
        {
            $rowset = $this->select_groups();
            foreach ($rowset as $row) {
                $this->template->assign_block_vars('A', $row);
            }
            return $this->helper->render($tpl_name, $cfg['title']);
        }
    }

    public function delete_valid($cfg)
    {
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        $tbl = $this->container->getParameter('jeb.snahp.tables');
        $gid = (int) $this->request->variable('gid', '0');
        if (!$gid)
        {
            $this->send_message(['status'=>'ERROR', 'i' => $total, 'total' => $total]);
            return $js->send([]);
        }
        $where = "group_id={$gid}";
        $sql = 'SELECT COUNT(*) as total FROM ' . USERS_TABLE . " WHERE $where";
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        $total = $row['total'];
        $sql = 'SELECT * FROM ' . USERS_TABLE . " WHERE $where";
        $i = 0;
        $this->send_message(['status'=>'START', 'i' => $i, 'total' => $total]);
        try
        {
            $result = $this->db->sql_query($sql);
            while($row = $this->db->sql_fetchrow($result))
            {
                $i += 1;
                $user_id = $row['user_id'];
                $invite_user_data = $this->invite_helper->select_invite_users($where="i.user_id={$user_id}");
                if (!$invite_user_data) continue;
                $sql = 'UPDATE ' . $tbl['invite_users'] . ' SET ' .
                    'n_available=0 WHERE user_id=' . $user_id;
                $this->db->sql_query($sql);
                $this->invite_helper->delete_valid_invite_from_user($user_id);
                if ($i % 10 == 0)
                {
                    $this->send_message(['status' => 'PROGRESS', 'i' => $i, 'total' => $total]);
                }
            }
            $this->db->sql_freeresult($result);
        }
        catch (\Exception $e)
        {
            $this->send_message(['status'=>'ERROR', 'i' => $total, 'total' => $total]);
        }
        $this->send_message(['status'=>'COMPLETE', 'i' => $total, 'total' => $total]);
        $js = new \phpbb\json_response();
        $js->send([]);
    }

    public function giveaway_invtes($cfg)
    {
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        $tbl = $this->container->getParameter('jeb.snahp.tables');
        $gid = (int) $this->request->variable('gid', '0');
        if (!$gid)
        {
            $this->send_message(['status'=>'ERROR', 'i' => $total, 'total' => $total]);
            return $js->send([]);
        }
        $n_to_give = (int) $this->request->variable('n', 1);
        $where = "group_id={$gid}";
        $sql = 'SELECT COUNT(*) as total FROM ' . USERS_TABLE . " WHERE $where";
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        $total = $row['total'];
        $sql = 'SELECT * FROM ' . USERS_TABLE . " WHERE $where";
        $i = 0;
        $this->send_message(['status'=>'START', 'i' => $i, 'total' => $total]);
        try
        {
            $result = $this->db->sql_query($sql);
            while($row = $this->db->sql_fetchrow($result))
            {
                $i += 1;
                $user_id = $row['user_id'];
                $invite_user_data = $this->invite_helper->select_invite_users($where="i.user_id={$user_id}");
                if (!$invite_user_data) continue;
                $sql = 'UPDATE ' . $tbl['invite_users'] . ' SET ' .
                    'n_available = n_available+' . $n_to_give . ' WHERE user_id=' . $user_id;
                $this->db->sql_query($sql);
                if ($i % 10 == 0)
                {
                    $this->send_message(['status' => 'PROGRESS', 'i' => $i, 'total' => $total]);
                }
            }
            $this->db->sql_freeresult($result);
        }
        catch (\Exception $e)
        {
            $this->send_message(['status'=>'ERROR', 'i' => $total, 'total' => $total]);
        }
        $this->send_message(['status'=>'COMPLETE', 'i' => $total, 'total' => $total]);
        $js = new \phpbb\json_response();
        $js->send([]);
    }

    public function insert_invite_users_by_group($cfg)
    {
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        $gid = (int) $this->request->variable('gid', '0');
        if (!$gid) return $js->send([]);
        $n_to_give = (int) $this->request->variable('n', 0);
        $where = "group_id={$gid}";
        $sql = 'SELECT COUNT(*) as total FROM ' . USERS_TABLE . " WHERE $where";
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        $total = $row['total'];
        $sql = 'SELECT * FROM ' . USERS_TABLE . " WHERE $where";
        $result = $this->db->sql_query($sql);
        $i = 0;
        $this->send_message(['status'=>'START', 'i' => $i, 'total' => $total]);
        try
        {
            while($row = $this->db->sql_fetchrow($result))
            {
                $i += 1;
                $user_id = $row['user_id'];
                $this->invite_helper->insert_invite_users([$user_id], $n_to_give);
                if ($i % 10 == 0)
                {
                    $this->send_message(['status' => 'PROGRESS', 'i' => $i, 'total' => $total]);
                }
            }
            $this->db->sql_freeresult($result);
        }
        catch(\Exception $e)
        {
            $this->send_message(['status'=>'ERROR', 'i' => $total, 'total' => $total]);
        }
        $this->send_message(['status'=>'COMPLETE', 'i' => $total, 'total' => $total]);
        $js = new \phpbb\json_response();
        $js->send([]);
    }

    public function test($mode)
    {
        $data = [];
        $js = new \phpbb\json_response();
        $js->send($data);
    }

    function select_user($where, $b_list=false, $start=0, $limit=100)
    {
        $sql = 'SELECT * FROM ' . USERS_TABLE . 
            " WHERE $where";
        if ($b_list)
        {
            $result = $this->db->sql_query_limit($sql, $limit, $start);
            $rowset = $this->db->sql_fetchrowset($result);
            $this->db->sql_freeresult($result);
            return $rowset;
        }
        else
        {
            $result = $this->db->sql_query($sql);
            $row = $this->db->sql_fetchrow($result);
            $this->db->sql_freeresult($result);
            return $row;
        }
    }

    public function send_message($data) {
        echo "data: " . json_encode($data) . PHP_EOL;
        echo PHP_EOL;
        ob_flush();
        flush();
    }

}
