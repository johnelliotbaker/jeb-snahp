<?php
namespace jeb\snahp\core\foe_blocker;

class helper
{
    protected $db;
    protected $auth;
    protected $user;
    protected $container;
    protected $this_user_id;
	public function __construct(
        $db, $user, $auth, $container,
        $tbl,
        $sauth
	)
	{
        $this->db = $db;
        $this->user = $user;
        $this->auth = $auth;
        $this->container = $container;
        $this->tbl = $tbl;
        $this->sauth = $sauth;
        $this->user_id = $this->user->data['user_id'];
        $this->block_data = [];
	}

    public function setup_block_data()/*{{{*/
    {
        $user_id = $this->user_id;
        $tbl = $this->tbl;
        $params = $this->container->getParameter('jeb.snahp.foe_blocker');
        $block_status = $params['status']['block'];
        $sql = 'SELECT blocked_id FROM ' . $tbl['foe'] . " WHERE blocker_id=${user_id} AND status=${block_status}";
        $result = $this->db->sql_query($sql);
        $rowset = $this->db->sql_fetchrowset($result);
        $this->db->sql_freeresult($result);
        $this->block_data = array_map(function($arg){return $arg['blocked_id'];}, $rowset);
        $this->block_data[] = $user_id;
    }/*}}}*/

    public function can_block($blocked_id, $blocker_id=null)/*{{{*/
    {
        if ($blocker_id===null) $blocker_id = $this->user_id;
        if ($blocker_id==$blocked_id || $this->sauth->user_belongs_to_groupset($blocked_id, 'Staff'))
        {
            return false;
        }
        else
        {
            return in_array($blocked_id, $this->block_data) ? false : true;
        }
    }/*}}}*/

    public function is_blocked_with_blocker_id($blocked_id, $blocker_id)/*{{{*/
    {
        $def_block = $this->container->getParameter('jeb.snahp.foe_blocker')['status']['block'];
        $sql = 'SELECT 1 FROM ' . $this->tbl['foe'] . " WHERE blocked_id=${blocked_id} AND blocker_id=${blocker_id} AND status=${def_block}";
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        return !empty($row);
    }/*}}}*/

    public function select_blocked_data($blocked_id, $blocker_id)/*{{{*/
    {
        $def_block = $this->container->getParameter('jeb.snahp.foe_blocker')['status']['block'];
        $sql = 'SELECT * FROM ' . $this->tbl['foe'] . " WHERE blocked_id=${blocked_id} AND blocker_id=${blocker_id} AND status=${def_block}";
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        return $row;
    }/*}}}*/

    public function is_blocked_with_post_id($blocked_id, $post_id)/*{{{*/
    {
        $post_id = (int) $post_id;
        $sql_array = [
            'SELECT'       => '1',
            'FROM'         => [ POSTS_TABLE => 'a', ],
            'LEFT_JOIN'    => [
                [
                    'FROM' => [$this->tbl['foe'] => 'b'],
                    'ON'   => 'a.poster_id=b.blocker_id',
                ],
            ],
            'WHERE'        => "a.post_id=${post_id} AND b.blocked_id=${blocked_id}",
        ];
        $sql = $this->db->sql_build_query('SELECT', $sql_array);
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        return !empty($row);
    }/*}}}*/

    public function cannot_pm($blocked_id, $post_id)/*{{{*/
    {
        $post_id = (int) $post_id;
        $sql_array = [
            'SELECT'       => '1',
            'FROM'         => [ POSTS_TABLE => 'a', ],
            'LEFT_JOIN'    => [
                [
                    'FROM' => [$this->tbl['foe'] => 'b'],
                    'ON'   => 'a.poster_id=b.blocker_id',
                ],
            ],
            'WHERE'        => "a.post_id=${post_id} AND b.blocked_id=${blocked_id} AND allow_pm=0",
        ];
        $sql = $this->db->sql_build_query('SELECT', $sql_array);
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        return !empty($row);
    }/*}}}*/

    public function cannot_reply($blocked_id, $blocker_id)/*{{{*/
    {
        $blocked_id = (int) $blocked_id;
        $blocker_id = (int) $blocker_id;
        $sql_array = [
            'SELECT'       => '1',
            'FROM'         => [$this->tbl['foe'] => 'a'],
            'WHERE'        => "a.blocker_id=${blocker_id} AND a.blocked_id=${blocked_id} AND a.allow_reply=0",
        ];
        $sql = $this->db->sql_build_query('SELECT', $sql_array);
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        return !empty($row);
    }/*}}}*/

}
