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
        case 'save_order':
            $cfg = [];
            return $this->save_tpl_order($cfg);
            break;
        case 'create':
            $cfg = [];
            return $this->create_tpl($cfg);
            break;
        case 'delete':
            $cfg = [];
            return $this->del_tpl($cfg);
            break;
        default:
            break;
        }
        trigger_error('Error Code: 62869cedad');
    }

    private function reject_overdraw($user_id)
    {
        $js = new \phpbb\json_response();
        $user_id = (int) $user_id;
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
        $this->reject_overdraw($user_id);
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

    public function save_tpl_order($cfg)
    {
        $js = new \phpbb\json_response();
        $user_id = $this->user->data['user_id'];
        $items = json_decode(htmlspecialchars_decode($this->request->variable('items', '', true)), true);
        foreach($items as $item)
        {
          $priority = (int) $item['priority'];
          $name = $this->db->sql_escape($item['name']);
          $sql = "UPDATE phpbb_snahp_tpl SET priority=${priority} WHERE user_id=${user_id} AND name='${name}';";
          $this->db->sql_query($sql);
        }
        $status = true;
        $js->send([ 'status' => $status ? 'success' : 'fail', ]);
    }

    public function del_tpl($cfg)
    {
        $js = new \phpbb\json_response();
        $user_id = $this->user->data['user_id'];
        $name = $this->request->variable('n', '');
        $sql = $this->delete_tpl($user_id, $name);
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

}
