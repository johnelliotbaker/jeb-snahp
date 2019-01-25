<?php
namespace jeb\snahp\mcp;

function prn($var) {
    if (is_array($var))
    { foreach ($var as $k => $v) { echo "$k => "; prn($v); }
    } else { echo "$var<br>"; }
}

use jeb\snahp\core\base;

class main_module extends base
{
	public $page_title;
	public $tpl_name;
	public $u_action;

	public function main($id, $mode)
	{
		global $config, $request, $template, $user, $phpbb_container;

		$user->add_lang_ext('jeb/snahp', 'common');
		$this->page_title = $user->lang('MCP_SNP_TITLE');

        $cfg = array();
        $this->def = $phpbb_container->getParameter('jeb.snahp.req')['def'];
        $this->tbl = $phpbb_container->getParameter('jeb.snahp.tables');
        switch ($mode)
        {
        case 'dibs':
            $cfg['tpl_name'] = 'mcp_snp_list_dibs';
            $cfg['b_feedback'] = false;
            $this->handle_list_dibs($cfg);
            break;
        case 'ban':
            $cfg['tpl_name'] = 'mcp_snp_dibs_ban';
            $cfg['b_feedback'] = false;
            $this->handle_dibs_ban($cfg);
            break;
        case 'request':
            $cfg['tpl_name'] = 'mcp_snp_list_request';
            $cfg['b_feedback'] = false;
            $this->handle_list_request($cfg);
            break;
        }
        if (!empty($cfg)){
            $this->handle_default($cfg);
        }
	}

    public function select_topics()
    {
        global $db;
        $sql = 'SELECT * from ' . TOPICS_TABLE;
        $result = $db->sql_query($sql);
        $data = [];
        while ($row = $db->sql_fetchrow($result))
            $data[] = $row;
        $db->sql_freeresult($result);
        return $data;
    }

    public function select_groups()
    {
        global $db;
        $sql = 'SELECT * from ' . GROUPS_TABLE;
        $result = $db->sql_query($sql);
        $data = [];
        while ($row = $db->sql_fetchrow($result))
            $data[] = $row;
        $db->sql_freeresult($result);
        return $data;
    }

    public function select_request_users($uid)
    {
        $sql = 'SELECT * FROM ' . $this->req_users_tbl ." WHERE user_id=$uid";
        $result = $db->sql_query($sql);
        $row = $db->sql_fetchrow($result);
        $db->sql_freeresult($result);
        return $row;
    }
    public function handle_default($cfg)
    {
		global $config, $request, $template, $user, $db;
        // prn(array_keys($GLOBALS)); // Lists all available globals
        $tpl_name = $cfg['tpl_name'];
        if ($tpl_name)
        {
            $this->tpl_name = $tpl_name;
            add_form_key('jeb_snp');
            if ($request->is_set_post('submit'))
            {
                if (!check_form_key('jeb_snp'))
                {
                    trigger_error('FORM_INVALID', E_USER_WARNING);
                }
                // trigger_error('Handling default');
            }
            $template->assign_vars(array(
                'U_ACTION'				=> $this->u_action,
            ));
        }
    }

    public function select_requests_for_pagi($per_page, $start)
    {
        global $db, $table_prefix;
        $p = $table_prefix;
        $sql = 'SELECT ' .
                'users.username, request.*, topics.* ' .
                'FROM ' .
                    $p . 'snahp_request AS request ' .
                    'LEFT JOIN ' .
                        USERS_TABLE . ' AS users ON request.requester_uid=users.user_id ' .
                    'LEFT JOIN ' .
                    TOPICS_TABLE .' AS topics ON request.tid=topics.topic_id ' .
                    'ORDER BY id DESC';
        $result = $db->sql_query_limit($sql, $per_page, $start);
        $data = [];
        while($row = $db->sql_fetchrow($result))
        {
            $data[] = $row;
        }
        $db->sql_freeresult($result);
        return $data;
    }

    public function select_undibs_for_pagi($per_page, $start)
    {
        global $db, $table_prefix;
        $p = $table_prefix;
        $sql = 'SELECT ' .
                'users.username, dibs.*, topics.* ' .
                'FROM ' .
                    $p . 'snahp_dibs AS dibs ' .
                    'LEFT JOIN ' .
                        USERS_TABLE . ' AS users ON dibs.dibber_uid=users.user_id ' .
                    'LEFT JOIN ' .
                    TOPICS_TABLE .' AS topics ON dibs.tid=topics.topic_id ' .
                    'WHERE status=5 ' .
                    'ORDER BY undib_time DESC';
        $result = $db->sql_query_limit($sql, $per_page, $start);
        $data = [];
        while($row = $db->sql_fetchrow($result))
        {
            $data[] = $row;
        }
        $db->sql_freeresult($result);
        return $data;
    }

    public function select_total($tbl, $condition)
    {
        global $db;
        $sql_arr = [
            'SELECT'   => 'count(*) as total',
            'FROM'     => [$tbl => 'dibs'],
            'WHERE'    => $condition,
        ];
        $sql    = $db->sql_build_query('SELECT', $sql_arr);
        $result = $db->sql_query($sql);
        $row    = $db->sql_fetchrow($result);
        $db->sql_freeresult($result);
        return $row['total'];
    }

    public function select_user_modal($mode, $data)
    {
        global $db;
        switch ($mode)
        {
        case 'user_id':
            $where = 'user_id="'. $data . '"';
            break;
        case 'username':
            $where = 'username_clean="'. $data . '"';
            break;
        default:
            return false;
        }
        $sql_arr = [
            'SELECT'   => '*',
            'FROM'     => [USERS_TABLE => 'users'],
            'WHERE'    => $where,
        ];
        $sql    = $db->sql_build_query('SELECT', $sql_arr);
        $result = $db->sql_query($sql);
        $row    = $db->sql_fetchrow($result);
        $db->sql_freeresult($result);
        return $row;
    }

    public function enable_dibber($mode, $data, $b_enable=true)
    {
        global $db;
        switch ($mode)
        {
        case 'user_id':
            $where = ' WHERE user_id="' . $data . '"';
            break;
        case 'username':
            $data = utf8_clean_string($data);
            $where = ' WHERE username_clean="' . $data . '"';
            break;
        default:
            return false;
        }
        if ($where && $row = $this->select_user_modal($mode, $data))
        {
            $status = $b_enable ? 1 : 0;
            $sql_arr = [
                'snp_req_dib_enable' => $status,
            ];
            $sql = 'UPDATE ' . USERS_TABLE . ' SET
                ' . $db->sql_build_array('UPDATE', $sql_arr) . $where;
            $db->sql_query($sql);
            return true;
        }
        return false;
    }

    public function handle_list_dibs($cfg)
    {
		global $config, $request, $template, $user, $db, $phpbb_container;
        $tpl_name = $cfg['tpl_name'];
        if ($tpl_name)
        {
            $this->tpl_name = $tpl_name;
            add_form_key('jeb_snp');
            if ($request->is_set_post('submit'))
            {
                if (!check_form_key('jeb_snp'))
                {
                    trigger_error('FORM_INVALID', E_USER_WARNING);
                }
                meta_refresh(2, $this->u_action);
                trigger_error($user->lang('MCP_SNP_SETTING_SAVED') . adm_back_link($this->u_action));
            }
            $per_page = $config['posts_per_page'];
            $start = $request->variable('start', 0);
            $dibdata = $this->select_undibs_for_pagi($per_page, $start);
            $total = $this->select_total($this->tbl['dibs'], 'status='.$this->def['undib']);
            $pagination = $phpbb_container->get('pagination');
            $base_url = $this->u_action;
            $pagination->generate_template_pagination(
                $base_url, 'pagination', 'start', $total, $per_page, $start, true
            );
            foreach($dibdata as $row)
            {
                $tid = $row['tid'];
                $topic_time = $user->format_date($row['topic_time']);
                $undib_time = $user->format_date($row['undib_time']);
                $u_details = '/viewtopic.php?t=' . $tid;
                $group = array(
                    'DIBBER_USERNAME' => $row['username'],
                    'DIB_TIME'        => $row['dib_time'],
                    'UNDIB_TIME'      => $undib_time,
                    'TOPIC_TITLE'     => $row['topic_title'],
                    'TOPIC_POSTER'    => $row['topic_first_poster_name'],
                    'TOPIC_TIME'      => $topic_time,
                    'U_VIEW_DETAILS'  => $u_details,
                );
                $template->assign_block_vars('postrow', $group);
            };
            $template->assign_vars(array(
                'B_ENABLE' => $config['snp_b_request'],
                'U_ACTION' => $this->u_action,
            ));
        }
    }

    public function handle_dibs_ban($cfg)
    {
		global $config, $request, $template, $user, $db, $phpbb_container;
        $tpl_name = $cfg['tpl_name'];
        if ($tpl_name)
        {
            $this->tpl_name = $tpl_name;
            add_form_key('jeb_snp');
            if ($request->is_set_post('submit'))
            {
                if (!check_form_key('jeb_snp'))
                {
                    trigger_error('FORM_INVALID', E_USER_WARNING);
                }
                $a_username = $request->variable('ban', '');
                $a_username = preg_split("#" . PHP_EOL . "|,#", $a_username);
                $b_enable = $request->variable('banexclude', '1');
                foreach ($a_username as $username)
                {
                    $b_success = $this->enable_dibber('username', $username, $b_enable);
                    if (!$b_success)
                        prn('Could not process ' . $username);
                }
            }
            $per_page = $config['posts_per_page'];
            $start = $request->variable('start', 0);
            $reqdata = $this->select_requests_for_pagi($per_page, $start);
            $total = $this->select_total($this->tbl['req'], 'TRUE');
            $pagination = $phpbb_container->get('pagination');
            $base_url = $this->u_action;
            $pagination->generate_template_pagination(
                $base_url, 'pagination', 'start', $total, $per_page, $start, true
            );
            foreach($reqdata as $row)
            {
                $tid = $row['tid'];
                $topic_time = $user->format_date($row['topic_time']);
                $u_details = '/viewtopic.php?t=' . $tid;
                $group = array(
                    'TOPIC_TITLE'     => $row['topic_title'],
                    'TOPIC_POSTER'    => $row['topic_first_poster_name'],
                    'TOPIC_TIME'      => $topic_time,
                    'U_VIEW_DETAILS'  => $u_details,
                );
                $template->assign_block_vars('postrow', $group);
            };
            $template->assign_vars(array(
                'B_ENABLE' => $config['snp_b_request'],
                'U_ACTION'    => $this->u_action,
            ));
        }
    }

    public function handle_list_request($cfg)
    {
		global $config, $request, $template, $user, $db, $phpbb_container;
        $tpl_name = $cfg['tpl_name'];
        if ($tpl_name)
        {
            $this->tpl_name = $tpl_name;
            add_form_key('jeb_snp');
            if ($request->is_set_post('submit'))
            {
                if (!check_form_key('jeb_snp'))
                {
                    trigger_error('FORM_INVALID', E_USER_WARNING);
                }
                meta_refresh(2, $this->u_action);
                trigger_error($user->lang('MCP_SNP_SETTING_SAVED') . adm_back_link($this->u_action));
            }
            $per_page = $config['posts_per_page'];
            $start = $request->variable('start', 0);
            $reqdata = $this->select_requests_for_pagi($per_page, $start);
            $total = $this->select_total($this->tbl['req'], 'TRUE');
            $pagination = $phpbb_container->get('pagination');
            $base_url = $this->u_action;
            $pagination->generate_template_pagination(
                $base_url, 'pagination', 'start', $total, $per_page, $start, true
            );
            foreach($reqdata as $row)
            {
                $tid = $row['tid'];
                $topic_time = $user->format_date($row['topic_time']);
                $u_details = '/viewtopic.php?t=' . $tid;
                $group = array(
                    'TOPIC_TITLE'     => $row['topic_title'],
                    'TOPIC_POSTER'    => $row['topic_first_poster_name'],
                    'TOPIC_TIME'      => $topic_time,
                    'U_VIEW_DETAILS'  => $u_details,
                );
                $template->assign_block_vars('postrow', $group);
            };
            $template->assign_vars(array(
                'B_ENABLE' => $config['snp_b_request'],
                'U_ACTION'    => $this->u_action,
            ));
        }
    }

}
