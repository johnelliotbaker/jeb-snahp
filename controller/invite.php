<?php

namespace jeb\snahp\controller;

use jeb\snahp\core\base;
use Symfony\Component\HttpFoundation\Response;
use \Symfony\Component\HttpFoundation\JsonResponse;

function prn($var) {
    if (is_array($var))
    { foreach ($var as $k => $v) { echo "... $k => "; prn($v); }
    } else { echo "$var<br>"; }
}

class invite extends base
{

    protected $table_prefix;
    protected $invite_helper;

    public function __construct($table_prefix)
    {
        $this->table_prefix = $table_prefix;
    }

	public function handle($mode)
    {
        $this->reject_anon();
        // $this->tbl = $this->container->getParameter('jeb.snahp.tables');
        // $this->def = $this->container->getParameter('jeb.snahp.req')['def'];
        $this->invite_helper = new \jeb\snahp\core\invite_helper($this->container, $this->user, $this->auth, $this->request, $this->db, $this->config, $this->helper, $this->template);
        switch ($mode)
        {
            case 'list_json': // To solve request
                $cfg = [];
                $this->get_list_json($cfg);
                break;
            case 'get_invite_user_json': // To solve request
                $cfg = [];
                $this->get_invite_user_json($cfg);
                break;
            case 'generate_invite_json': // To solve request
                $cfg = [];
                $this->generate_invite_json($cfg);
                break;
            case 'insert_invite_user':
                $cfg = [];
                $this->handle_insert_invite_user($cfg);
                break;
            case 'disable_invite':
                $cfg = [];
                $this->handle_disable_invite($cfg);
                break;
            default:
                break;
        }
        trigger_error('Error');
    }

    public function handle_insert_invite_user($cfg)
    {
        $username = $this->request->variable('u', '');
        if (!$username) $username = $this->user->data['username_clean'];
        $username = utf8_clean_string($username);
        $user_data = $this->invite_helper->select_user($where="username_clean='$username'");
        $uid = $user_data['user_id'];
        $js = new \phpbb\json_response();
        if (!$uid) $js->send([]);
        $this->invite_helper->insert_invite_users([$uid]);
    }

    public function handle_disable_invite($cfg)
    {
        $iid = (int) $this->request->variable('iid', '');
        if (!$iid) return false;
        $this->invite_helper->disable_invite($iid);
        $js = new \phpbb\json_response();
        $js->send(['iid' => $iid]);
    }

    public function get_list_json($cfg)
    {
        $username = $this->request->variable('u', '');
        if (!$username) $username = $this->user->data['username_clean'];
        $username = utf8_clean_string($username);
        $user_data = $this->invite_helper->select_user($where="username_clean='$username'");
        $uid = $user_data['user_id'];
        $js = new \phpbb\json_response();
        if (!$uid) $js->send([]);
        $rowset = $this->invite_helper->get_invite_list($uid, false);
        $js = new \phpbb\json_response();
        $js->send($rowset);
    }

    public function get_invite_user_json($cfg)
    {
        $username = $this->request->variable('u', '');
        if (!$username) $username = $this->user->data['username_clean'];
        $username = utf8_clean_string($username);
        $user_data = $this->invite_helper->select_user($where="username_clean='$username'");
        $uid = $user_data['user_id'];
        $js = new \phpbb\json_response();
        if (!$uid) $js->send(['invitation_id' => -1]);
        $rowset = $this->invite_helper->get_invite_user_list($uid);
        $js->send($rowset);
    }

    public function generate_invite_json($cfg)
    {
        $uid = $this->request->variable('uid', '');
        if (!$uid) $uid = $this->user->data['user_id'];
        $uuid = $this->invite_helper->insert_invite($uid);
        $js = new \phpbb\json_response();
        $js->send(['keyphrase' => $uuid]);
    }

}
