<?php
namespace jeb\snahp\controller;
use \Symfony\Component\HttpFoundation\Response;
use jeb\snahp\core\base;

class template extends base
{
    protected $prefix;

    public function __construct($prefix)
    {
        $this->prefix = $prefix;
    }

    public function handle($mode)
    {
        $group_id = $this->user->data['group_id'];
        $this->reject_group('snp_customtemplate_enable', $group_id);
        switch ($mode)
        {
        case 'serialize':
            $cfg = [];
            return $this->get_serialize($cfg);
            break;
        case 'get':
            $cfg = [];
            return $this->get_tpl($cfg);
            break;
        case 'save':
            $cfg = [];
            return $this->save_tpl($cfg);
            break;
        case 'create':
            $cfg = [];
            return $this->create_tpl($cfg);
            break;
        case 'delete':
            $cfg = [];
            return $this->del_tpl($cfg);
            break;
        // case 'test':
        //     $cfg = [];
        //     return $this->test($cfg);
        //     break;
        // case 'test2':
        //     $cfg = [];
        //     return $this->test2($cfg);
        //     break;
        // case 'test_save':
        //     $cfg = [];
        //     return $this->test_save($cfg);
        //     break;
        // case 'test_delete':
        //     $cfg = [];
        //     return $this->test_delete($cfg);
        //     break;
        // case 'test_create':
        //     $cfg = [];
        //     return $this->test_create($cfg);
        //     break;
        default:
            break;
        }
        trigger_error('Error Code: 62869cedad');
    }

    private function reject_overdraw()
    {
        $js = new \phpbb\json_response();
        $user_id = $this->user->data['user_id'];
        $gid = $this->user->data['group_id'];
        $sql = 'SELECT snp_customtemplate_n_max FROM ' . GROUPS_TABLE . ' WHERE group_id = ' . $gid;
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        if (!$row)
        {
            $js->send(['status' => 'fail', 'reason' => 'Error Code: 6a9f4aa7ab']);
            trigger_error('Error Code: d4b7daa1b9');
        }
        $n_max = $row['snp_customtemplate_n_max'];
        $rowset = $this->select_tpl($user_id, false);
        $count = count($rowset);
        if ($count >= $n_max)
        {
            $js->send(['status' => 'fail', 'reason' => 'You have exceeded your quota. Error Code: 6ab9e5ab1d']);
            trigger_error('You have exceeded your quota. Error Code: 6ab9e5ab1d');
        }
    }

    public function create_tpl($cfg)
    {
        $js = new \phpbb\json_response();
        $user_id = $this->user->data['user_id'];
        $this->reject_overdraw();
        $name = $this->request->variable('name', '');
        $name = preg_replace("/[^A-Za-z0-9_ ]/", '', $name);
        $text = $this->request->variable('text', '', true);
        if ($text)
        {
          $sql = $this->upsert_tpl($user_id, $name, $text);
        }
        $js->send(['status' => $sql ? 'success' : 'fail']);
    }

    public function save_tpl($cfg)
    {
        $js = new \phpbb\json_response();
        $user_id = $this->user->data['user_id'];
        $name = $this->request->variable('name', '');
        $text = $this->request->variable('text', '', true);
        $priority = $this->request->variable('priority', 0);
        if ($text)
        {
          $sql = $this->update_tpl($user_id, $name, $text, $priority);
        }
        $js->send(['status' => $sql ? 'success' : 'fail']);
    }

    public function del_tpl($cfg)
    {
        $js = new \phpbb\json_response();
        $u = $this->user->data['user_id'];
        $n = $this->request->variable('n', '');
        $sql = $this->delete_tpl($u, $n);
        $js->send(['status' => $sql ? 'success' : 'fail']);
    }

    public function get_tpl($cfg)
    {
        $js = new \phpbb\json_response();
        $b_full = (bool) $this->request->variable('full', false);
        $name = $this->request->variable('name', '');
        $user_id = $this->user->data['user_id'];
        $rowset = $this->select_tpl($user_id, $b_full, $name);
        $js->send($rowset);
    }

    public function test($cfg)
    {
        $js = new \phpbb\json_response();
        $b_full = (bool) $this->request->variable('full', false);
        $name = $this->request->variable('name', '');
        $user_id = 2;
        $rowset = $this->select_tpl($user_id, $b_full, $name);
        $js->send($rowset);
    }

    public function test2($cfg)
    {
        $js = new \phpbb\json_response();
        $text = $this->request->variable('text', '', true);
        $name = $this->request->variable('name', '', true);
        $js->send(['text' => $text, 'name' => $name]);
    }

    public function test_save($cfg)
    {
        $user_id = 2;
        $js = new \phpbb\json_response();
        $name = $this->request->variable('name', '');
        $text = $this->request->variable('text', '', true);
        if ($text)
        {
            $sql = $this->update_tpl($user_id, $name, $text);
        }
        $js->send(['status' => 'success',
            'user_id'=>$user_id,
            'name'=>$name,
            'text'=>$text]);
    }

    public function test_delete($cfg)
    {
        $u = 2;
        $n = $this->request->variable('n', '');
        $this->delete_tpl($u, $n);
        $js = new \phpbb\json_response();
        $js->send([ 'status' => 'success' ]);
    }

    public function test_create($cfg)
    {
        $js = new \phpbb\json_response();
        $user_id = 2;
        // $this->reject_overdraw();
        $name = $this->request->variable('name', '');
        $name = preg_replace("/[^A-Za-z0-9_ ]/", '', $name);
        $text = $this->request->variable('text', '', true);
        if ($text)
        {
          $sql = $this->upsert_tpl($user_id, $name, $text);
        }
        $js->send(['sql'=>$sql, 'status' => $sql ? 'success' : 'fail']);
        $js->send(['status' => 'success', 'name'=>$name, 'text'=>$text]);
    }

}
