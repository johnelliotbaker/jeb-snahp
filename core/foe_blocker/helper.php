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

    public function is_blocked_with_blocker_username($blocked_id, $blocker_username)/*{{{*/
    {
        $blocker_id = $this->username2userid($blocker_username);
        $def_block = $this->container->getParameter('jeb.snahp.foe_blocker')['status']['block'];
        $sql = 'SELECT 1 FROM ' . $this->tbl['foe'] . " WHERE blocked_id=${blocked_id} AND blocker_id=${blocker_id} AND status=${def_block}";
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        return !empty($row);
    }/*}}}*/

    public function select_blocked_data_by_username($username)/*{{{*/
    {
        $username = (string) $username;
        $user_id = $this->username2userid($username);
        $def_block = $this->container->getParameter('jeb.snahp.foe_blocker')['status']['block'];
        $sql_array = [
            'SELECT'	=> 'a.*,
            b.username as blocked_username, b.user_colour as blocked_user_colour,
             c.username as blocker_username, c.user_colour as blocker_user_colour',
            'FROM'		=> [$this->tbl['foe'] => 'a'],
            'LEFT_JOIN'	=> [
                [
                    'FROM'	=> [USERS_TABLE => 'b'],
                    'ON'	=> 'a.blocked_id=b.user_id',
                ],
                [
                    'FROM'	=> [USERS_TABLE => 'c'],
                    'ON'	=> 'a.blocker_id=c.user_id',
                ],
            ],
            'WHERE'		=> "a.blocked_id=${user_id} OR a.blocker_id=${user_id}",
            'ORDER_BY' => "c.username ASC"
        ];
        $sql = $this->db->sql_build_query('SELECT', $sql_array);
        $sql = 'SELECT t.* FROM (' . $sql . ') t ORDER BY blocked_username ASC';
        $result = $this->db->sql_query($sql);
        $rowset = $this->db->sql_fetchrowset($result);
        $this->db->sql_freeresult($result);
        return $rowset;
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

    public function cannot_pm_with_blocker_id($blocked_id, $blocker_id)/*{{{*/
    {
        $blocker_id = (int) $blocker_id;
        $blocked_id = (int) $blocked_id;
        $sql_array = [
            'SELECT'       => '1',
            'FROM'         => [ $this->tbl['foe'] => 'a', ],
            'WHERE'        => "a.blocked_id=${blocked_id} AND a.blocker_id=${blocker_id} AND a.allow_pm=0",
        ];
        $sql = $this->db->sql_build_query('SELECT', $sql_array);
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        return !empty($row);
    }/*}}}*/

    public function cannot_pm_with_post_id($blocked_id, $post_id)/*{{{*/
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

    public function toggle_permission_type($blocked_id, $blocker_id, $permission_type)/*{{{*/
    {
        $allowed_permission_types = [
            'viewtopic' => 'allow_viewtopic',
            'reply' => 'allow_reply',
            'quote' => 'allow_quote',
            'pm' => 'allow_pm',
        ];
        $blocked_id = (int) $blocked_id;
        $blocker_id = (int) $blocker_id;
        $permission_type =  $this->db->sql_escape($permission_type);
        if (!$this->is_blocked_with_blocker_id($blocked_id, $blocker_id))
        {
            return false;
        }
        if (!array_key_exists($permission_type, $allowed_permission_types))
        {
            return false;
        }
        $column_name = $allowed_permission_types[$permission_type];
        $sql = 'UPDATE ' . $this->tbl['foe'] . " SET ${column_name}=1-${column_name}" .
            " WHERE blocked_id=${blocked_id} AND blocker_id=${blocker_id}";
        $this->db->sql_query($sql);
        return $this->db->sql_affectedrows() > 0;
    }/*}}}*/

    public function toggle_perma_block($blocked_id, $blocker_id)/*{{{*/
    {
        $blocked_id = (int) $blocked_id;
        $blocker_id = (int) $blocker_id;
        if (!$this->is_blocked_with_blocker_id($blocked_id, $blocker_id))
        {
            return false;
        }
        $sql = 'UPDATE ' . $this->tbl['foe'] . ' SET b_permanent=1-b_permanent' .
            " WHERE blocked_id=${blocked_id} AND blocker_id=${blocker_id}";
        $this->db->sql_query($sql);
        return $this->db->sql_affectedrows() > 0;
    }/*}}}*/

    public function toggle_freeze($blocked_id, $blocker_id)/*{{{*/
    {
        $blocked_id = (int) $blocked_id;
        $blocker_id = (int) $blocker_id;
        if (!$this->is_blocked_with_blocker_id($blocked_id, $blocker_id))
        {
            return false;
        }
        $sql = 'UPDATE ' . $this->tbl['foe'] . ' SET b_frozen=1-b_frozen' .
            " WHERE blocked_id=${blocked_id} AND blocker_id=${blocker_id}";
        $this->db->sql_query($sql);
        return $this->db->sql_affectedrows() > 0;
    }/*}}}*/

    public function update_mod_reason($blocked_id, $blocker_id, $mod_reason)/*{{{*/
    {
        $blocked_id = (int) $blocked_id;
        $blocker_id = (int) $blocker_id;
        // $mod_reason = $this->db->sql_escape((string) $mod_reason);
        if (!$this->is_blocked_with_blocker_id($blocked_id, $blocker_id))
        {
            return false;
        }
        $data = [
            'mod_reason' => $mod_reason,
            'mod_id' => $this->user_id,
        ];
        $sql = 'UPDATE ' . $this->tbl['foe'] . '
            SET ' . $this->db->sql_build_array('UPDATE', $data) . "
            WHERE blocked_id=${blocked_id} AND blocker_id=${blocker_id}";
        $this->db->sql_query($sql);
        return $this->db->sql_affectedrows() > 0;
    }/*}}}*/

    public function username2userid($username)/*{{{*/
    {
        $username_clean = utf8_clean_string($this->db->sql_escape($username));
        $sql = 'SELECT user_id from ' . USERS_TABLE . " WHERE username_clean='${username_clean}'";
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        return $row ? (int) $row['user_id'] : 0;
    }/*}}}*/

    public function format_userlist($rowset)/*{{{*/
    {
        foreach($rowset as &$row)
        {
            $created_time = $row['created_time'];
            $duration = $row['duration'];
            $row['local_time'] = $this->user->format_date($created_time, 'y.m.d');
            $row['expires'] = $this->user->format_date($created_time + $duration, 'y.m.d');
            $row['block_reason'] = stripslashes($row['block_reason']);
            $row['mod_reason'] = stripslashes($row['mod_reason']);
        }
        return $rowset;
    }/*}}}*/

}
