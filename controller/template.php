<?php
namespace jeb\snahp\controller;
use \Symfony\Component\HttpFoundation\Response;
use jeb\snahp\core\base;

function prn($var, $b_html=false) {
    if (is_array($var))
    { foreach ($var as $k => $v) { echo "... $k => "; prn($v, $b_html); }
    } else {
        if ($b_html)
        {
            echo htmlspecialchars($var) . '<br>';
        }
        else
        {
            echo $var . '<br>';
        }
    }
}

class template extends base
{
    protected $prefix;

    public function __construct($prefix)
    {
        $this->prefix = $prefix;
    }

    public function handle($mode)
    {
        $this->reject_anon();
        $this->reject_bots();
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
        default:
            break;
        }
        trigger_error('Error Code: 62869cedad');
    }

    public function create_tpl($cfg)
    {
        $js = new \phpbb\json_response();
        $user_id = $this->user->data['user_id'];
        if ($this->request->is_ajax())
        {
            // $vn = $this->request->variable_names();
            $name = htmlspecialchars_decode($this->request->variable('name', ''));
            $text = htmlspecialchars_decode($this->request->variable('text', ''));
            if ($text)
            {
                $sql = $this->upsert_tpl($user_id, $name, $text);
            }
            $js->send([$sql]);
        }
        $js->send(['error' => 'Must request with ajax.']);
    }

    public function save_tpl($cfg)
    {
        $js = new \phpbb\json_response();
        $user_id = $this->user->data['user_id'];
        if ($this->request->is_ajax())
        {
            // $vn = $this->request->variable_names();
            $text = htmlspecialchars_decode($this->request->variable('text', ''));
            $name = $this->request->variable('name', '');
            if ($text)
            {
                $sql = $this->update_tpl($user_id, $name, $text);
            }
            $js->send([]);
        }
        $js->send(['error' => 'Must request with ajax.']);
    }

    public function del_tpl($cfg)
    {
        $u = $this->user->data['user_id'];
        $n = $this->request->variable('n', '');
        $this->delete_tpl($u, $n);
        $js = new \phpbb\json_response();
        $js->send([]);
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
