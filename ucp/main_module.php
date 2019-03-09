<?php
namespace jeb\snahp\ucp;

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


class main_module
{
	var $u_action;

	function main($id, $mode)
	{
		global $db, $request, $template, $user;

        $cfg = array();
        switch ($mode)
        {
        case 'visibility':
            $cfg['tpl_name'] = 'ucp_snp_vis';
            $cfg['b_feedback'] = true;
            $this->handle_visibility($cfg);
            break;
        case 'invite':
            $cfg['tpl_name'] = '@jeb_snahp/ucp/invite/invite';
            $cfg['b_feedback'] = true;
            $this->handle_invite($cfg);
            break;
        }
        if (!empty($cfg)){
            $this->handle_mode($cfg);
        }
	}

    function handle_invite($cfg)
    {
		global $phpbb_container, $user, $auth, $request, $db, $config, $helper, $template;
        if (!$config['snp_inv_b_master'])
        {
            return;
        }
        $user_id = $user->data['user_id'];
        // Using config invite master switch
        $b_enable = true;
        $template->assign_vars([ 'B_ENABLE' => $b_enable, ]);
        $ih = new \jeb\snahp\core\invite_helper($phpbb_container, $user, $auth, $request, $db, $config, $helper, $template);
        $invite_users_data = $ih->select_invite_users($where="i.user_id={$user_id}");
        if ($invite_users_data)
        {
            $invite_users_data = $invite_users_data[0];
            $template->assign_vars([
                'B_INVITE_USER_EXISTS' => true,
                'N_AVAILABLE' => $invite_users_data['n_available'],
                'B_BAN' => $invite_users_data['b_ban'],
                'S_BAN_MSG_PUBLIC' => $invite_users_data['ban_msg_public'],
            ]);
        }
        else
        {
            $template->assign_vars([
                'B_INVITE_USER_EXISTS' => false,
                'N_AVAILABLE' => 0,
            ]);
        }

        // Pagination
        $start = (int) $request->variable('start', '0');
        $total = $ih->select_invite_total($where="inviter_id={$user_id}");
        $per_page = 10;
        $pg = new \jeb\snahp\core\pagination();
        $base_url = '/ucp.php?i=-jeb-snahp-ucp-main_module&mode=invite';
        $pagination = $pg->make($base_url, $total, $per_page, $start);
        $template->assign_vars([
            'PAGINATION' => $pagination,
        ]);
        // Data retrieval using pagination
        $invite_data = $ih->get_invite_list($user_id, $b_digest=true , $start=$start, $limit=$per_page);
        if ($invite_data)
        {
            $template->assign_vars([
                'B_INVITE_EXISTS' => true,
            ]);
            $count = $start;
            foreach ($invite_data as $row)
            {
                $row['id'] = $total - $count++;
                $template->assign_block_vars('A_INVITE', $row);
            }
        }
    }

    function handle_visibility($cfg)
    {
		global $db, $request, $template, $user;
        $user_id = $user->data['user_id'];
        $this->tpl_name = $cfg['tpl_name'];
        $this->page_title = $user->lang('UCP_SNP_TITLE');
        add_form_key('jeb/snahp');
        $data = [
            'snp_disable_avatar_thanks_link' => $request->variable('snp_disable_avatar_thanks_link', $user->data['snp_disable_avatar_thanks_link']),
            'snp_enable_at_notify'           => $request->variable('snp_enable_at_notify', $user->data['snp_enable_at_notify']),
        ];
        if ($request->is_set_post('submit'))
        {
            if (!check_form_key('jeb/snahp'))
            {
                trigger_error($user->lang('FORM_INVALID'));
            }
            $sql = 'UPDATE ' . USERS_TABLE . '
                SET ' . $db->sql_build_array('UPDATE', $data) . '
                WHERE user_id = ' . $user->data['user_id'];
            $result = $db->sql_query($sql);
            $db->sql_freeresult($result);
            if ($cfg['b_feedback'])
            {
                meta_refresh(2, $this->u_action);
                $message = $user->lang('UCP_SNP_SAVED') . '<br /><br />' . $user->lang('RETURN_UCP', '<a href="' . $this->u_action . '">', '</a>');
                trigger_error($message);
            }
        }
        $template_vars = array(
            'S_SNP_DISABLE_AVATAR_THANKS_LINK' => $data['snp_disable_avatar_thanks_link'],
            'S_SNP_ENABLE_AT_NOTIFY'           => $data['snp_enable_at_notify'],
            'S_UCP_ACTION'	=> $this->u_action,
        );
        $template->assign_vars($template_vars);
    }

    function handle_mode($cfg)
    {
		global $db, $request, $template, $user;
        $this->tpl_name = $cfg['tpl_name'];
        $this->page_title = $user->lang('UCP_SNP_TITLE');
        add_form_key('jeb/snahp');
        if ($request->is_set_post('submit'))
        {
            if (!check_form_key('jeb/snahp'))
            {
                trigger_error($user->lang('FORM_INVALID'));
            }
            if ($cfg['b_feedback'])
            {
                meta_refresh(2, $this->u_action);
                $message = $user->lang('UCP_SNP_SAVED') . '<br /><br />' . $user->lang('RETURN_UCP', '<a href="' . $this->u_action . '">', '</a>');
                trigger_error($message);
            }
        }
        $template->assign_vars([]);
    }

}
