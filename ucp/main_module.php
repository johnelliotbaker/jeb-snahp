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
        case 'custom':
            $cfg['tpl_name'] = '@jeb_snahp/ucp/customize/base';
            $cfg['b_feedback'] = true;
            $this->handle_custom($cfg);
            break;
        case 'block':
            $cfg['tpl_name'] = '@jeb_snahp/ucp/block/base';
            $cfg['b_feedback'] = true;
            $this->handle_block($cfg);
            break;
        }
        if (!empty($cfg)){
            $this->handle_mode($cfg);
        }
    }

    private function is_custom_rank_purchased($user_id)/*{{{*/
    {
        global $phpbb_container, $user, $auth, $request, $db, $config, $helper, $template;
        $tbl = $phpbb_container->getParameter('jeb.snahp.tables');
        $sql = 'SELECT * FROM ' . $tbl['mrkt_product_classes'] . " WHERE name='custom_rank'";
        $result = $db->sql_query($sql);
        $row = $db->sql_fetchrow($result);
        $db->sql_freeresult($result);
        if (!$row) { return false; }
        $pcid = $row['id'];
        $sql = 'SELECT * FROM ' . $tbl['user_inventory'] . " WHERE user_id=${user_id} AND product_class_id=${pcid}";
        $result = $db->sql_query($sql);
        $row = $db->sql_fetchrow($result);
        $db->sql_freeresult($result);
        if (!$row) { return false; }
        return true ? $row['quantity'] > 0 : false;
    }/*}}}*/

    private function get_quick_search_upgrade_count($user_id)/*{{{*/
    {
        global $phpbb_container, $user, $auth, $request, $db, $config, $helper, $template;
        $user_id = (int) $user_id;
        $tbl = $phpbb_container->getParameter('jeb.snahp.tables');
        $sql = 'SELECT * FROM ' . $tbl['mrkt_product_classes'] . " WHERE name='search_cooldown_reducer'";
        $result = $db->sql_query($sql);
        $row = $db->sql_fetchrow($result);
        $db->sql_freeresult($result);
        $pcid = $row['id'];
        $sql = 'SELECT * FROM ' . $tbl['user_inventory'] . " WHERE user_id=${user_id} AND product_class_id=${pcid}";
        $result = $db->sql_query($sql);
        $row = $db->sql_fetchrow($result);
        $db->sql_freeresult($result);
        if ($row)
        {
            return $row['quantity'];
        }
        return 0;
    }/*}}}*/

    function handle_block($cfg)/*{{{*/
    {
        global $phpbb_container, $user, $auth, $request, $db, $config, $helper, $template;
        $sauth = $phpbb_container->get('jeb.snahp.auth.user_auth');
        $user_id = $user->data['user_id'];
        $b_enable = $config['snp_foe_b_master'];
        $b_permission = !$sauth->user_belongs_to_groupset($user_id, 'Basic');
        $template->assign_vars([
            'B_ENABLE' => $b_enable,
            'B_PERMISSION' => $b_permission,
        ]);
    }/*}}}*/

    private function showThanksResetter($userId)/*{{{*/
    {
        global $phpbb_container, $template;
        $helper = $phpbb_container->get('jeb.snahp.ThanksResetCycleHelper');
        $tokens = $helper->getThanksResetTokenCount($userId);
        $gaveAllAvailableThanks = $helper->hasGivenAllAvailableThanks($userId);
        $shouldShow = $tokens;
        $template->assign_var('B_THANKS_RESET', $shouldShow);
    }/*}}}*/
    
    function handle_custom($cfg)/*{{{*/
    {
        global $phpbb_container, $user, $auth, $request, $db, $config, $helper, $template;
        if (!$config['snp_ucp_custom_b_master'])
        {
            return;
        }
        $user_id = $user->data['user_id'];
        $group_id = $user->data['group_id'];
        $b_enable = true;
        $group_data = $this->select_group($group_id);
        $n_quick_search = $this->get_quick_search_upgrade_count($user_id);
        $overall_search_interval = max($group_data['snp_search_interval'] - $n_quick_search, 5);
        $template->assign_vars([
            'B_ENABLE' => $b_enable,
            // 'B_PERMISSION' => $this->user_belongs_to_groupset($user_id, 'Red Team'),
            'B_PERMISSION' => true,
            'GROUP_DATA' => $group_data,
            'N_QUICK_SEARCH' => $n_quick_search,
            'OVERALL_SEARCH_INTERVAL' => $overall_search_interval,
            'B_CUSTOM_RANK' => $this->is_custom_rank_purchased($user_id),
        ]);
        $this->showThanksResetter($user_id);
        // Using config custom master switch
    }/*}}}*/

    function handle_invite($cfg)/*{{{*/
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
    }/*}}}*/

    function handle_visibility($cfg)/*{{{*/
    {
        global $db, $request, $template, $user;
        $user_id = $user->data['user_id'];
        $this->tpl_name = $cfg['tpl_name'];
        $this->page_title = $user->lang('UCP_SNP_TITLE');
        add_form_key('jeb/snahp');
        $data = [
            'snp_disable_avatar_thanks_link' => $request->variable('snp_disable_avatar_thanks_link', $user->data['snp_disable_avatar_thanks_link']),
            'snp_thanks_b_topic'             => $request->variable('snp_thanks_b_topic', $user->data['snp_thanks_b_topic']),
            'snp_enable_at_notify'           => $request->variable('snp_enable_at_notify', $user->data['snp_enable_at_notify']),
            'snp_rep_b_avatar'               => $request->variable('snp_rep_b_avatar', $user->data['snp_rep_b_avatar']),
            'snp_rep_b_profile'              => $request->variable('snp_rep_b_profile', $user->data['snp_rep_b_profile']),
            'snp_achi_b_avatar'              => $request->variable('snp_achi_b_avatar', $user->data['snp_achi_b_avatar']),
            'snp_achi_b_profile'             => $request->variable('snp_achi_b_profile', $user->data['snp_achi_b_profile']),
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
            'SNP_THANKS_B_TOPIC'               => $data['snp_thanks_b_topic'],
            'S_SNP_ENABLE_AT_NOTIFY'           => $data['snp_enable_at_notify'],
            'SNP_REP_B_AVATAR'                 => $data['snp_rep_b_avatar'],
            'SNP_REP_B_PROFILE'                => $data['snp_rep_b_profile'],
            'SNP_ACHI_B_AVATAR'                => $data['snp_achi_b_avatar'],
            'SNP_ACHI_B_PROFILE'               => $data['snp_achi_b_profile'],
            'S_UCP_ACTION'  => $this->u_action,
        );
        $template->assign_vars($template_vars);
    }/*}}}*/

    function handle_mode($cfg)/*{{{*/
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
    }/*}}}*/

    public function reject_user_not_in_groupset($user_id, $groupset_name)/*{{{*/
    {
        if (!$this->user_belongs_to_groupset($user_id, $groupset_name))
        {
            trigger_error('You do not have the permission to view this page. Error Code: ad5611c89b');
        }
    }/*}}}*/

    public function user_belongs_to_groupset($user_id, $groupset_name)/*{{{*/
    {
        global $phpbb_container, $user, $auth, $request, $db, $config, $helper, $template;
        if ($this->is_dev_server())
        {
            $groupset = $phpbb_container->getParameter('jeb.snahp.groups')['dev']['set'];
        }
        else
        {
            $groupset = $phpbb_container->getParameter('jeb.snahp.groups')['production']['set'];
        }
        include_once('includes/functions_user.php');
        $user_id_ary = [$user_id];
        if (!array_key_exists($groupset_name, $groupset))
        {
            return false;
        }
        $group_id_ary = $groupset[$groupset_name];
        $res = group_memberships($group_id_ary, $user_id_ary);
        return !!$res;
    }/*}}}*/

    public function is_dev_server()/*{{{*/
    {
        global $config;
        $servername = $config['server_name'];
        return isset($servername) && $servername=='192.168.2.12';
    }/*}}}*/

    private function select_group($group_id)/*{{{*/
    {
        global $db;
        $group_id = (int) $group_id;
        $sql = 'SELECT * FROM ' . GROUPS_TABLE . " WHERE group_id=${group_id}";
        $result = $db->sql_query($sql);
        $row = $db->sql_fetchrow($result);
        $db->sql_freeresult($result);
        return $row;
    }/*}}}*/

}
