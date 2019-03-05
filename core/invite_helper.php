<?php

/**
 * undocumented function
 *
 * @return void
 */
namespace jeb\snahp\core;

use phpbb\template\context;
use phpbb\user;
use phpbb\auth\auth;
use phpbb\request\request_interface;
use phpbb\db\driver\driver_interface;
use phpbb\config\config;
use phpbb\controller\helper;
use phpbb\language\language;
use phpbb\template\template;
use phpbb\notification\manager;


class invite_helper
{
    protected $phpbb_container;
    protected $user;
    protected $auth;
    protected $request;
    protected $db;
    protected $config;
    protected $helper;
    protected $template;

	public function __construct(
        $phpbb_container, $user, $auth, $request, $db, $config, $helper, $template
	)
	{
        $this->phpbb_container = $phpbb_container;
        $this->user = $user;
        $this->auth = $auth;
        $this->request = $request;
        $this->db = $db;
        $this->config = $config;
        $this->helper = $helper;
        $this->template = $template;
	}

    
    // DATABASE Functions
    // INVITE USERS
    public function select_invite_users($where, $order_by='')
    {
        $limit = 100;
        $tbl = $this->phpbb_container->getParameter('jeb.snahp.tables')['invite_users'];
        $sql_array = [
            'SELECT'	=> 'i.*, u.username, u.user_id, u.user_colour ',
            'FROM'		=> [$tbl => 'i'],
            'LEFT_JOIN'	=> [
                [
                    'FROM'	=> [USERS_TABLE => 'u'],
                    'ON'	=> 'i.user_id=u.user_id',
                ],
            ],
            'WHERE'		=> $where,
            'ORDER_BY' => $order_by,
        ];
        $sql = $this->db->sql_build_query('SELECT', $sql_array);
        $result = $this->db->sql_query_limit($sql, $limit);
        $rowset = $this->db->sql_fetchrowset($result);
        $this->db->sql_freeresult($result);
        return $rowset;
    }

    // INVITE
    public function update_invite($id, $data)
    {
        $tbl = $this->phpbb_container->getParameter('jeb.snahp.tables');
        $sql = 'UPDATE ' . $tbl['invite'] . '
            SET ' . $this->db->sql_build_array('UPDATE', $data) . '
            WHERE id=' . $id;
        $this->db->sql_query($sql);
    }

    public function redeem_invite($keyphrase='', $user_id)
    {
        if (!$keyphrase)
        {
            trigger_error('You must provide a valid invitation code.');
        }
        $invite_data = $this->select_invite($where="keyphrase='$keyphrase'");
        if (!$invite_data) trigger_error('The invititation code is invalid.');
        $invite_data = $invite_data[0];
        $data = [
            'b_active' => 0,
            'redeemer_id' => $user_id,
            'redeem_time' => time(),
        ];
        $this->update_invite($invite_data['id'], $data);
    }

    public function update_invite_users($a_user_id, $data)
    {
        $tbl = $this->phpbb_container->getParameter('jeb.snahp.tables');
        foreach ($a_user_id as $key=>$user_id)
        {
            $user_data = $this->select_user($where="user_id=$user_id");
            if (!$user_data) continue;
            $invite_user_data = $this->select_invite_users($where="i.user_id={$user_id}");
            if (!$invite_user_data) continue;
            $sql = 'UPDATE ' . $tbl['invite_users'] . ' SET ' .
                $this->db->sql_build_array('UPDATE', $data) . '
                WHERE user_id=' . $user_id;
            $this->db->sql_query($sql);
        }
    }

    public function insert_invite_users($a_user_id, $n_available=0)
    {
        $tbl = $this->phpbb_container->getParameter('jeb.snahp.tables');
        foreach ($a_user_id as $user_id)
        {
            $user_data = $this->select_user($where="user_id=$user_id");
            if (!$user_data) continue;
            $invite_user_data = $this->select_invite_users($where="i.user_id={$user_id}");
            if ($invite_user_data) continue;
            $data = [
                'user_id'   => $user_id,
                'ban_msg_private'  => '', // Unicode text needs manual init
                'n_available' => $n_available,
            ];
            $sql = 'INSERT INTO ' . $tbl['invite_users'] . 
                $this->db->sql_build_array('INSERT', $data);
            $this->db->sql_query($sql);
        }
    }

    public function disable_invite($iid)
    {
        $data = ['b_active' => 0];
        $this->update_invite($iid, $data);
    }

    public function insert_invite($inviter_id)
    {
        $invite_user_data = $this->select_invite_users($where="i.user_id={$inviter_id}");
        if (!$invite_user_data) return false;
        $invite_user_data = $invite_user_data[0];
        if ($invite_user_data['n_available'] <= 0 || $invite_user_data['b_ban']) return false;
        $tbl = $this->phpbb_container->getParameter('jeb.snahp.tables');
        $uuid = uniqid('', true);
        $data = [
            'keyphrase'   => $uuid,
            'inviter_id'  => $inviter_id,
            'create_time' => time(),
        ];
        $sql = 'INSERT INTO ' . $tbl['invite'] . 
            $this->db->sql_build_array('INSERT', $data);
        $this->db->sql_query($sql);

        $sql = 'UPDATE ' . $tbl['invite_users'] . ' SET
            n_available = n_available - 1 WHERE
            user_id=' . $inviter_id;
        $this->db->sql_query($sql);
        return $uuid;
    }

    public function select_invite($where, $order_by='')
    {
        $limit = 100;
        $tbl = $this->phpbb_container->getParameter('jeb.snahp.tables')['invite'];
        $sql_array = [
            'SELECT'	=> 'i.*, u.username, u.user_id, u.user_colour ',
            'FROM'		=> [$tbl => 'i'],
            'LEFT_JOIN'	=> [
                [
                    'FROM'	=> [USERS_TABLE => 'u'],
                    'ON'	=> 'i.redeemer_id=u.user_id',
                ],
            ],
            'WHERE'		=> $where,
            'ORDER_BY' => $order_by,
        ];
        $sql = $this->db->sql_build_query('SELECT', $sql_array);
        $result = $this->db->sql_query_limit($sql, $limit);
        $rowset = $this->db->sql_fetchrowset($result);
        $this->db->sql_freeresult($result);
        return $rowset;
    }

    function delete_valid_invite_from_user($user_id)
    {
        $user_data = $this->select_user($where="user_id={$user_id}");
        if (!$user_data) return false;
        $tbl = $this->phpbb_container->getParameter('jeb.snahp.tables')['invite'];
        $sql = 'DELETE FROM ' . $tbl . " WHERE inviter_id={$user_id} AND b_active=1";
        $this->db->sql_query($sql);
        return true;
    }

    function get_invite_user_list($user_id=null)
    {
        if (!$user_id) $user_id = $this->user->data['user_id'];
        $rowset = $this->select_invite_users($where="i.user_id={$user_id}");
        if (!$rowset) return [];
        $row = $rowset[0];
        $entry = [];
        $entry['invitation_id'] = $row['id'];
        $entry['user_id'] = $row['user_id'];
        $entry['current_available'] = $row['n_available'];
        $entry['total_created'] = $row['n_total_issued'];
        $entry['can_redeem'] = $row['b_redeem_enable'];
        $entry['banned'] = $row['b_ban'];
        $entry['times_banned'] = $row['n_ban'];
        $entry['last_banned_time'] = $row['ban_time'];
        $entry['public_ban_message'] = $row['ban_msg_public'];
        $entry['internal_ban_message'] = $row['ban_msg_private'];
        return $entry;
    }

    function select_user($where)
    {
        $sql = 'SELECT * FROM ' . USERS_TABLE . 
            " WHERE $where";
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        return $row;
    }

    function get_invite_list($user_id=null, $b_digest=true)
    {
        if (!$user_id) $user_id = $this->user->data['user_id'];
        $rowset = $this->select_invite($where="inviter_id={$user_id}", $order_by='id DESC');
        foreach ($rowset as $k => $row)
        {
            if ($b_digest)
            {
                unset($row['create_time']);
                unset($row['id']);
                unset($row['expiration_time']);
            }
            else
            {
                $row['create_time'] = $row['create_time'] ? $this->user->format_date($row['create_time']) : '';
                $row['expiration_time'] = $row['expiration_time'] ? $this->user->format_date($row['expiration_time']) : '';
            }
            $row['redeem_time'] = $row['redeem_time'] ? $this->user->format_date($row['redeem_time']) : '';
            $row['status_strn'] = $row['b_active'] ? '<span style="color:#0B0"><b>VALID</b></span>' : '<span style="color:#B00"><b>VOID</b></span>' ;
            $redeemer = $this->make_username($row);
            $row['redeemer'] = $redeemer;
            $rowset[$k] = $row;
        } 
        return $rowset;
    }

    public function make_username($row)
    {
        $strn = '<a href="/memberlist.php?mode=viewprofile&u='. $row['user_id'] . '" style="color: #'. $row['user_colour'] .'">' . $row['username'] . '</a>';
        return $strn;
    }

    public function is_admin()
    {
        return $this->auth->acl_gets('a_');
    }

    public function is_mod()
    {
        return $this->auth->acl_gets('a_', 'm_');
    }

    // public function select_user($user_id)
    // {
    //     $sql = 'SELECT * FROM ' . USERS_TABLE ." WHERE user_id=$user_id";
    //     $result = $this->db->sql_query($sql);
    //     $row = $this->db->sql_fetchrow($result);
    //     $this->db->sql_freeresult($result);
    //     return $row;
    // }

}
