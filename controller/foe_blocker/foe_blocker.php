<?php
namespace jeb\snahp\controller\foe_blocker;
use \Symfony\Component\HttpFoundation\Response;
use \Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Forum foe_blocker
 * */

class foe_blocker
{
    protected $db;
    protected $user;
    protected $config;
    protected $request;
    protected $template;
    protected $container;
    protected $helper;
    protected $tbl;
    protected $sauth;
    protected $foe_helper;
    public function __construct(
    $db, $user, $config, $request, $template, $container, $helper,
    $tbl,
    $sauth, $foe_helper
    )/*{{{*/
    {
        $this->db = $db;
        $this->user = $user;
        $this->config = $config;
        $this->request = $request;
        $this->template = $template;
        $this->container = $container;
        $this->helper = $helper;
        $this->tbl = $tbl;
        $this->sauth = $sauth;
        $this->foe_helper = $foe_helper;
        $this->user_id = $this->user->data['user_id'];
        $this->redirect_time = 3;
    }/*}}}*/

    public function handle($mode)/*{{{*/
    {
        if ($this->sauth->user_belongs_to_groupset($this->user_id, 'Basic'))
        {
            trigger_error('You do not have permission to view this page. Error Code: 805345a533');
        }
        switch($mode)
        {
        case 'manage':
            $cfg['tpl_name'] = '@jeb_snahp/foe_blocker/base.html';
            return $this->respond_manage($cfg);
        case 'block':
            return $this->respond_block();
        case 'unblock':
            return $this->respond_unblock();
        }
        trigger_error('Invalide mode. Error Code: 1ac9ef6655');
    }/*}}}*/

    private function select_blocked_users($blocker_id=null, $blocked_id=null)/*{{{*/
    {
        $blocker_id = $blocker_id===null ? $this->user_id : (int) $blocker_id;
        $where = "a.blocker_id=${blocker_id}";
        if ($blocked_id!==null)
        {
            $blocked_id = (int) $blocked_id;
            $where .= " AND a.blocked_id=${blocked_id}";
        }
        $sql_array = [
            'SELECT'    => 'a.*, b.username as blocked_username',
            'FROM'      => [ $this->tbl['foe'] => 'a', ],
            'LEFT_JOIN' => [
                [
                    'FROM' => [USERS_TABLE => 'b'],
                    'ON'   => 'a.blocked_id=b.user_id',
                ],
            ],
            'WHERE'    => $where,
            'ORDER_BY' => "b.username_clean ASC"
        ];
        $sql = $this->db->sql_build_query('SELECT', $sql_array);
        $result = $this->db->sql_query($sql);
        $rowset = $this->db->sql_fetchrowset($result);
        $this->db->sql_freeresult($result);
        return $rowset;
    }/*}}}*/

    private function username2userid($username)/*{{{*/
    {
        $username_clean = utf8_clean_string($this->db->sql_escape($username));
        $sql = 'SELECT user_id from ' . USERS_TABLE . " WHERE username_clean='${username_clean}'";
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        return $row ? (int) $row['user_id'] : 0;
    }/*}}}*/

    private function select_post($post_id)/*{{{*/
    {
        $post_id = (int) $post_id;
        $sql_array = [
            'SELECT'	=> 'a.post_id, a.poster_id, b.username',
            'FROM'		=> [ POSTS_TABLE => 'a', ],
            'LEFT_JOIN'	=> [
                [
                    'FROM'	=> [USERS_TABLE => 'b'],
                    'ON'	=> 'a.poster_id=b.user_id',
                ],
            ],
            'WHERE'		=> "a.post_id='${post_id}'",
        ];
        $sql = $this->db->sql_build_query('SELECT', $sql_array);
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        return $row;
    }/*}}}*/

    private function get_or_reject_post($post_id)/*{{{*/
    {
        $post_data = $this->select_post($post_id);
        if (!$post_data) trigger_error('That post does not exist. Error Code: 64dfda57e1');
        return $post_data;
    }/*}}}*/

    private function validate_or_reject_duration($duration_strn)/*{{{*/
    {
        $duration_strn = (string) $duration_strn;
        $params = $this->container->getParameter('jeb.snahp.foe_blocker');
        $a_duration = $params['duration'];
        if (!array_key_exists($duration_strn, $a_duration)) trigger_error('Invalid duration. Error Code: a53f11de32');
        return (int) $a_duration[$duration_strn];
    }/*}}}*/

    private function validate_or_reject_block_reason($reason)/*{{{*/
    {
        $reason = (string) $reason;
        if (!$reason) trigger_error('You must provide a reason for blocking a user. Error Code: 4816c4661f');
        return $reason;
    }/*}}}*/

    private function format_userlist($rowset)/*{{{*/
    {
        foreach($rowset as &$row)
        {
            $until = $row['created_time'] + $row['duration'];
            $row['until'] = $this->user->format_date($until, 'Y-m-d');
        }
        return $rowset;
    }/*}}}*/

    private function respond_block()/*{{{*/
    {
        $params = $this->container->getParameter('jeb.snahp.foe_blocker');
        if ($this->request->is_set_post('block'))
        {
            if (!check_form_key('jeb_snp'))
            {
                trigger_error('FORM_INVALID', E_USER_WARNING);
            }
            $post_id = $this->request->variable('post_id', 0);
            $emergency_blocked_id = $this->request->variable('emergency_blocked_id', 0);
            if (!$post_id && $emergency_blocked_id)
            {
                return $this->respond_emergency_block();
            }
            $post_data = $this->get_or_reject_post($post_id);
            $block_reason = $this->request->variable('block_reason', '');
            $block_reason = $this->validate_or_reject_block_reason($block_reason);
            $duration_strn = $this->request->variable('duration', '');
            $duration = $this->validate_or_reject_duration($duration_strn);
            $allow_viewtopic = $this->request->variable('allow_viewtopic', 0);
            $allow_pm = $this->request->variable('allow_pm', 0);
            $allow_reply = $this->request->variable('allow_reply', 0);
            $blocker_id = $this->user_id;
            $this->block_user($post_data, $duration, $block_reason, $blocker_id, $allow_viewtopic, $allow_reply, $allow_pm);
            $u_action = $this->helper->route('jeb_snahp_routing.foe_blocker', ['mode'=>'manage']);
            meta_refresh($this->redirect_time, $u_action);
            trigger_error("<b>${post_data['username']}</b> has been blocked. Redirecting in {$this->redirect_time} seconds ...");
        }
        trigger_error('Invalid Form. Error Code: 8f57c0072b');
    }/*}}}*/

    private function respond_emergency_block()/*{{{*/
    {
        $params = $this->container->getParameter('jeb.snahp.foe_blocker');
        $blocked_id = $this->request->variable('emergency_blocked_id', 0);
        $block_reason = $this->request->variable('block_reason', '');
        $block_reason = $this->validate_or_reject_block_reason($block_reason);
        $duration_strn = 'three_days';
        $duration = $this->validate_or_reject_duration($duration_strn);
        $allow_viewtopic = $this->request->variable('allow_viewtopic', 0);
        $allow_pm = $this->request->variable('allow_pm', 0);
        $allow_reply = $this->request->variable('allow_reply', 0);
        $blocker_id = $this->user_id;
        $this->emergency_block_user($blocked_id, $duration, $block_reason, $blocker_id, $allow_viewtopic, $allow_reply, $allow_pm);
        $u_action = $this->helper->route('jeb_snahp_routing.foe_blocker', ['mode'=>'manage']);
        meta_refresh($this->redirect_time, $u_action);
        trigger_error("<b>${blocked_id}</b> has been blocked. Redirecting in {$this->redirect_time} seconds ...");
    }/*}}}*/

    private function respond_manage($cfg)/*{{{*/
    {
        $params = $this->container->getParameter('jeb.snahp.foe_blocker');
        add_form_key('jeb_snp');
        $rowset = $this->select_blocked_users($this->user_id);
        $rowset = $this->format_userlist($rowset);
        $this->template->assign_vars([
            'U_BLOCK' => $this->helper->route('jeb_snahp_routing.foe_blocker', ['mode'=>'block']),
            'U_UNBLOCK' => $this->helper->route('jeb_snahp_routing.foe_blocker', ['mode'=>'unblock']),
            'ROWSET' => $rowset,
        ]);
        return $this->helper->render($cfg['tpl_name'], 'Manage User Blocks');
    }/*}}}*/

    private function block_user($post_data, $duration, $block_reason, $blocker_id=null, $allow_viewtopic=0, $allow_reply=0, $allow_pm=0)/*{{{*/
    {
        if (!$this->foe_helper->can_block($post_data['poster_id']))
        {
            trigger_error("You cannot block <b>${post_data['username']}</b>. Error Code: 0dd26bc1a3");
        }
        $post_id = $post_data['post_id'];
        $blocked_id = $post_data['poster_id'];
        $params = $this->container->getParameter('jeb.snahp.foe_blocker');
        $blocker_id = $blocker_id===null ? $this->user_id : $blocker_id;
        $data = [
            'status' => $params['status']['block'],
            'blocker_id' => (int) $blocker_id,
            'blocked_id' => (int) $blocked_id,
            'allow_viewtopic' => (int) $allow_viewtopic,
            'allow_reply' => (int) $allow_reply,
            'allow_pm' => (int) $allow_pm,
            'post_id' => (int) $post_id,
            'block_reason' => (string) $block_reason,
            'created_time' => time(),
            'duration' => (int) $duration,
        ];
        $this->insert_or_update_foe($data);
    }/*}}}*/

    private function emergency_block_user($blocked_id, $duration, $block_reason, $blocker_id=null, $allow_viewtopic=0, $allow_reply=0, $allow_pm=0)/*{{{*/
    {
        $blocked_id = (int) $blocked_id;
        if (!$this->sauth->is_valid_user_id($blocked_id))
        {
            trigger_error('That user does not exist. Error Code: 8413238905');
        }
        $params = $this->container->getParameter('jeb.snahp.foe_blocker');
        $blocker_id = $blocker_id===null ? $this->user_id : $blocker_id;
        $data = [
            'status' => $params['status']['block'],
            'blocker_id' => (int) $blocker_id,
            'blocked_id' => (int) $blocked_id,
            'allow_viewtopic' => (int) $allow_viewtopic,
            'allow_reply' => (int) $allow_reply,
            'allow_pm' => (int) $allow_pm,
            'post_id' => (int) 0,
            'block_reason' => (string) $block_reason,
            'created_time' => time(),
            'duration' => (int) $duration,
        ];
        $this->insert_or_update_foe($data);
    }/*}}}*/

    private function insert_or_update_foe($data)/*{{{*/
    {
        $sql = 'INSERT INTO ' . $this->tbl['foe'] . $this->db->sql_build_array('INSERT', $data) . '
            ON DUPLICATE KEY UPDATE ' . $this->db->sql_build_array('UPDATE', $data);
        $this->db->sql_query($sql);
    }/*}}}*/

    private function insert_foe($blocker_id, $blocked_id, $post_id, $block_reason, $duration)/*{{{*/
    {
        if (count($block_reason) > 500) trigger_error('Please limit reason to 500 characters.');
        $data = [
            'blocker_id' => (int) $blocker_id,
            'blocked_id' => (int) $blocked_id,
            'post_id' => (int) $post_id,
            'block_reason' => (string) $block_reason,
            'duration' => (int) $duration,
        ];
        $sql = 'INSERT INTO ' . $this->tbl['foe'] . $this->db->sql_build_array('INSERT', $data);
    }/*}}}*/

    private function respond_unblock()/*{{{*/
    {
        $blocked_id = $this->request->variable('u', 0);
        $rowset = $this->select_blocked_users($this->user_id, $blocked_id);
        if (!$rowset) trigger_error('Invalid action. Error Code: 7447f12588');
        $row = $rowset[0];
        $this->unblock_user($blocked_id);
        $u_action = $this->helper->route('jeb_snahp_routing.foe_blocker', ['mode'=>'manage']);
        meta_refresh($this->redirect_time, $u_action);
        trigger_error("You have unblocked <b>${row['blocked_username']}</b>. Redirecting in {$this->redirect_time} seconds ...");
    }/*}}}*/

    private function unblock_user($blocked_id, $blocker_id=null)/*{{{*/
    {
        $blocker_id = $blocker_id===null ? $this->user_id : (int) $blocker_id;
        $this->remove_foe($blocked_id, $blocker_id);
    }/*}}}*/

    private function remove_foe($blocked_id, $blocker_id)/*{{{*/
    {
        $data = [
            'blocker_id' => (int) $blocker_id,
            'blocked_id' => (int) $blocked_id,
        ];
        $sql = 'DELETE FROM ' . $this->tbl['foe'] . ' WHERE ' . $this->db->sql_build_array('DELETE', $data);
        $this->db->sql_query($sql);
    }/*}}}*/

}
