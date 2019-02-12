<?php
/**
 *
 * snahp. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2018
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace jeb\snahp\acp;


function prn($var) {
    if (is_array($var))
    { foreach ($var as $k => $v) { echo "$k => "; prn($v); }
    } else { echo "$var<br>"; }
}


function buildSqlSetCase($casename, $varname, $arr)
{
    $strn = " SET $varname = CASE $casename ";
    foreach($arr as $k => $v)
    {
        $strn .= "WHEN '$k' THEN '$v' ";
    }
    $strn .= "ELSE $varname END ";
    return $strn;
}

function sanitize_fid($fid)
{
    $fid1 = explode(',', $fid);
    $fid_sane = [];
    foreach($fid1 as $k => $v)
    {
        if (is_numeric($v))
        {
            $fid_sane[] = preg_replace('/\s+/', '', $v);
        }
    }
    $fid_sane = array_unique($fid_sane);
    sort($fid_sane);
    $fid_sane = implode(',', $fid_sane);
    return $fid_sane;
}

/**
 * snahp ACP module.
 */
class main_module
{
	public $page_title;
	public $tpl_name;
	public $u_action;

	public function main($id, $mode)
	{
		global $config, $request, $template, $user;

		$user->add_lang_ext('jeb/snahp', 'common');
		$this->page_title = $user->lang('ACP_SNP_TITLE');

        $cfg = array();
        switch ($mode)
        {
        case 'donation':
            $cfg['tpl_name'] = 'acp_snp_donation';
            $cfg['b_feedback'] = false;
            $this->handle_donation($cfg);
            break;
        case 'signature':
            $cfg['tpl_name'] = 'acp_snp_signature';
            $cfg['b_feedback'] = false;
            $this->handle_signature($cfg);
            break;
        case 'imdb':
            $cfg['tpl_name'] = 'acp_snp_pg';
            $cfg['b_feedback'] = false;
            $this->handle_pg($cfg);
            break;
        case 'settings':
            $cfg['tpl_name'] = 'acp_snp_settings';
            $cfg['b_feedback'] = false;
            $this->handle_settings($cfg);
            break;
        case 'notification':
            $cfg['tpl_name'] = 'acp_snp_notification';
            $cfg['b_feedback'] = false;
            $this->handle_notification($cfg);
            break;
        case 'request':
            $cfg['tpl_name'] = 'acp_snp_request';
            $cfg['b_feedback'] = false;
            $this->handle_request($cfg);
            break;
        case 'scripts':
            $cfg['tpl_name'] = 'acp_snp_scripts';
            $cfg['b_feedback'] = false;
            break;
        }
        if (!empty($cfg)){
            $this->handle_default($cfg);
        }
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

    public function update_groups($casename, $varname, $arr)
    {
        global $db;
        $strn = " SET $varname = CASE $casename ";
        foreach($arr as $k => $v)
        {
            $strn .= "WHEN '$k' THEN '$v' ";
        }
        $strn .= "ELSE $varname END ";

        $sql = 'UPDATE ' . GROUPS_TABLE . $strn;
        $db->sql_query($sql);
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
                trigger_error($user->lang('ACP_SNP_SETTING_SAVED') . adm_back_link($this->u_action));
            }
            $template->assign_vars(array(
                'U_ACTION'				=> $this->u_action,
            ));
        }
    }

    public function handle_request($cfg)
    {
		global $config, $request, $template, $user, $db;
        $tpl_name = $cfg['tpl_name'];
        if ($tpl_name)
        {
            $this->tpl_name = $tpl_name;
            add_form_key('jeb_snp');
            $groupdata = $this->select_groups();
            if ($request->is_set_post('submit'))
            {
                if (!check_form_key('jeb_snp'))
                {
                    trigger_error('FORM_INVALID', E_USER_WARNING);
                }
                $config->set('snp_b_request', $request->variable('snp_b_request', '0'));
                $config->set('snp_req_fid', sanitize_fid($request->variable('request_fid', '0')));
                $config->set('snp_req_cycle_time', $request->variable('cycle_time', '0'));
                $config->set('snp_req_redib_cooldown_time', $request->variable('redib_cooldown_time', '0'));
                $fields = [
                // snp_req_XXXX => template name
                    'n_base'    => 'base',
                    'n_offset'  => 'offset',
                    'b_active'  => 'active',
                    'b_nolimit' => 'nolimit',
                    'n_cycle'   => 'cycle',
                ];
                foreach ($fields as $name => $field)
                {
                    $data = [];
                    foreach ($groupdata as $group)
                    {
                        $gid = $group['group_id'];
                        $html_name = "$field-$gid";
                        if ($name[0] == 'b')
                        {
                            $var = $request->variable($html_name, '0');
                            $var = $var ? 1 : 0;
                        }
                        else
                        {
                            $var = $request->variable($html_name, '0');
                        }
                        $data[$gid] = $var;
                    }
                    $this->update_groups('group_id', "snp_req_$name", $data);
                }
                // POSTFORM
                $postform_fid = unserialize($config['snp_req_postform_fid']);
                $postform_data = [];
                foreach($postform_fid as $name => $a_fid)
                {
                    $pf_fid_entry = $request->variable('postform-'.$name, 0);
                    $postform_data[$name] = $pf_fid_entry;
                }
                $config->set('snp_req_postform_fid', serialize($postform_data));
                // Graveyard
                $config->set('snp_cron_b_graveyard', $request->variable('cron_b_graveyard', '0'));
                $graveyard_fid['default'] = $request->variable('graveyard_fid', '0');
                $config->set('snp_cron_graveyard_fid', serialize($graveyard_fid));
                $config->set('snp_cron_graveyard_gc', $request->variable('cron_graveyard', '0'));
                $config->set('snp_cron_graveyard_last_gc', $request->variable('cron_graveyard_last', '0'));
                meta_refresh(2, $this->u_action);
                trigger_error($user->lang('ACP_SNP_SETTING_SAVED') . adm_back_link($this->u_action));
            }
            // Request Users Properties
            foreach($groupdata as $row)
            {
                $group = array(
                    'gid'     => $row['group_id'],
                    'gname'   => substr($row['group_name'], 0, 13),
                    'base'    => $row['snp_req_n_base'],
                    'offset'  => $row['snp_req_n_offset'],
                    'active'  => $row['snp_req_b_active'],
                    'nolimit' => $row['snp_req_b_nolimit'],
                    'cycle'   => $row['snp_req_n_cycle'],
                );
                $template->assign_block_vars('aSignature', $group);
            };
            // Postform FID
            $postform_fid = unserialize($config['snp_req_postform_fid']);
            foreach ($postform_fid as $name => $fid)
            {
                $fid_data['name'] = $name;
                $fid_data['fid'] = $fid;
                $template->assign_block_vars('POSTFORM_FID', $fid_data);
            }
            // Graveyard Request
            $graveyard_fid = unserialize($config['snp_cron_graveyard_fid'])['default'];
            $cron_last = $config['snp_cron_graveyard_last_gc'];
            $cron_last_human = $user->format_date($config['snp_cron_graveyard_last_gc']);
            $cron_interval = $config['snp_cron_graveyard_gc'];
            $cron_next = $cron_last + $cron_interval;
            $cron_next_human = $user->format_date($cron_next);

            $template->assign_vars(array(
                'SNP_B_REQUEST'        => $config['snp_b_request'],
                'request_fid'          => $config['snp_req_fid'],
                'redib_cooldown_time'  => $config['snp_req_redib_cooldown_time'],
                'cycle_time'           => $config['snp_req_cycle_time'],
                'CRON_B_GRAVEYARD'     => $config['snp_cron_b_graveyard'],
                'GRAVEYARD_FID'        => $graveyard_fid,
                'CRON_GRAVEYARD_LAST'  => $cron_last,
                'CRON_GRAVEYARD_LAST0' => $cron_last_human,
                'CRON_GRAVEYARD'       => $cron_interval,
                'CRON_GRAVEYARD_NEXT'  => $cron_next_human,
                'U_ACTION'             => $this->u_action,
            ));
        }
    }

    public function handle_notification($cfg)
    {
		global $config, $request, $template, $user, $db, $phpbb_container;
        $tpl_name = $cfg['tpl_name'];
        if ($tpl_name)
        {
            $this->tpl_name = $tpl_name;
            add_form_key('jeb_snp');
            if ($request->is_set_post('submit'))
            {
                $phpbb_notifications = $phpbb_container->get('notification_manager');
                if (!check_form_key('jeb_snp'))
                {
                    trigger_error('FORM_INVALID', E_USER_WARNING);
                }
                $config->set('snp_b_notify_op_on_report', $request->variable('b_notify_op_on_report', '0'));
                $config->set('snp_b_snahp_notify',        $request->variable('b_snahp_notify', '0'));
                $config->set('snp_b_notify_on_poke',      $request->variable('b_notify_on_poke', '0'));
                if ($config['snp_b_snahp_notify'])
                {
                    $phpbb_notifications->enable_notifications('jeb.snahp.notification.type.basic');
                } else {
                    $phpbb_notifications->disable_notifications('jeb.snahp.notification.type.basic');
                    // $phpbb_notifications->purge_notifications('jeb.snahp.notification.type.basic');
                }
                trigger_error($user->lang('ACP_SNP_SETTING_SAVED') . adm_back_link($this->u_action));
            }

            $template->assign_vars(array(
                'b_snahp_notify_checked'        => $config['snp_b_snahp_notify'],
                'b_notify_on_poke_checked'      => $config['snp_b_notify_on_poke'],
                'b_notify_op_on_report_checked' => $config['snp_b_notify_op_on_report'],
                'U_ACTION'                      => $this->u_action,
            ));
        }
    }

    public function handle_settings($cfg)
    {
		global $config, $request, $template, $user, $db;
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
                $fid_listings = $request->variable('fid_listings', '4');
                $config->set('snp_fid_listings', $fid_listings);
                $snp_ql_fav_limit = $request->variable('snp_ql_fav_limit', '300');
                $config->set('snp_ql_fav_limit', $snp_ql_fav_limit);
                $snp_ql_fav_duration = $request->variable('snp_ql_fav_duration', '86400');
                $config->set('snp_ql_fav_duration', $snp_ql_fav_duration);
                $snp_ql_fav_b_replies = $request->variable('snp_ql_fav_b_replies', '0');
                $config->set('snp_ql_fav_b_replies', $snp_ql_fav_b_replies);
                $snp_ql_fav_b_views = $request->variable('snp_ql_fav_b_views', '1');
                $config->set('snp_ql_fav_b_views', $snp_ql_fav_b_views);
                $snp_ql_fav_b_time = $request->variable('snp_ql_fav_b_time', '1');
                $config->set('snp_ql_fav_b_time', $snp_ql_fav_b_time);
                $snp_ql_thanks_given = $request->variable('snp_ql_thanks_given', '1');
                $config->set('snp_ql_thanks_given', $snp_ql_thanks_given);
                $snp_ql_ucp_bookmark = $request->variable('snp_ql_ucp_bookmark', '1');
                $config->set('snp_ql_ucp_bookmark', $snp_ql_ucp_bookmark);
                $snp_ql_req_open_requests = $request->variable('snp_ql_req_open_requests', '1');
                $config->set('snp_ql_req_open_requests', $snp_ql_req_open_requests);
                meta_refresh(2, $this->u_action);
                trigger_error($user->lang('ACP_SNP_SETTING_SAVED') . adm_back_link($this->u_action));
            }

            $template->assign_vars(array(
                'SNP_QL_FAV_LIMIT'         => $config['snp_ql_fav_limit'],
                'SNP_QL_FAV_DURATION'      => $config['snp_ql_fav_duration'],
                'SNP_QL_FAV_B_REPLIES'     => $config['snp_ql_fav_b_replies'],
                'SNP_QL_FAV_B_VIEWS'       => $config['snp_ql_fav_b_views'],
                'SNP_QL_FAV_B_TIME'        => $config['snp_ql_fav_b_time'],
                'SNP_QL_THANKS_GIVEN'      => $config['snp_ql_thanks_given'],
                'SNP_QL_UCP_BOOKMARK'      => $config['snp_ql_ucp_bookmark'],
                'SNP_QL_REQ_OPEN_REQUESTS' => $config['snp_ql_req_open_requests'],
                'FID_LISTINGS'             => $config['snp_fid_listings'],
                'U_ACTION'                 => $this->u_action,
            ));
        }
    }


    public function handle_signature($cfg)
    {
		global $config, $request, $template, $user, $db;
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
                $aSigRows = $aSigEnable = [];
                foreach ($request->variable_names() as $k => $varname)
                {
                    preg_match('/sig-(\d+)/', $varname, $match_sig_toggle);
                    if ($match_sig_toggle)
                    {
                        $gid = $match_sig_toggle[1];
                        $var = "sig-$gid";
                        $var = $request->variable($var, '0');
                        $aSigEnable[$gid] = $var ? 1 : 0;
                    }
                    preg_match('/sigrows-(\d+)/', $varname, $match_sig_rows);
                    if ($match_sig_rows)
                    {
                        $gid = $match_sig_rows[1];
                        $sid = "sigrows-$gid";
                        $var = $request->variable($sid, '0');
                        $aSigRows[$gid] = (int) $var;
                    }
                }
                $sql = 'UPDATE ' . GROUPS_TABLE . 
                    buildSqlSetCase('group_id', 'snp_enable_signature', $aSigEnable);
                $db->sql_query($sql);
                $sql = 'UPDATE ' . GROUPS_TABLE . 
                    buildSqlSetCase('group_id', 'snp_signature_rows', $aSigRows);
                $db->sql_query($sql);
                // trigger_error($user->lang('ACP_SNP_SETTING_SAVED') . adm_back_link($this->u_action));
            }
            $template->assign_vars(array(
                'U_ACTION'				=> $this->u_action,
            ));
            // Code to show signature configuration in ACP
            $sql = 'SELECT * from ' . GROUPS_TABLE;
            $result = $db->sql_query($sql);
            while ($row = $db->sql_fetchrow($result))
            {
                $group = array(
                    'gid'=>$row['group_id'],
                    'gname'=>$row['group_name'],
                    'gcheck'=> $row['snp_enable_signature'],
                    'grows'=> $row['snp_signature_rows'],
                );
                $template->assign_block_vars('aSignature', $group);
            };
            $db->sql_freeresult($result);
        }
    }

    public function handle_pg($cfg)
    {
		global $config, $request, $template, $user, $db, $table_prefix;
        $tpl_name = $cfg['tpl_name'];
        $pg_names = ['anime', 'listing', 'book', 'game'];
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
                // Code to limit IMDb based on group membership
                $aImdbEnable = [];
                foreach ($request->variable_names() as $k => $varname)
                {
                    preg_match('/imdb-(\d+)/', $varname, $match_imdb_toggle);
                    if ($match_imdb_toggle)
                    {
                        $gid = $match_imdb_toggle[1];
                        $var = "imdb-$gid";
                        $var = $request->variable($var, '0');
                        $aImdbEnable[$gid] = $var ? 1 : 0;
                    }
                }
                $sql = 'UPDATE ' . GROUPS_TABLE . 
                    buildSqlSetCase('group_id', 'snp_imdb_enable', $aImdbEnable);
                $db->sql_query($sql);
                // Code to limit anilist based on group membership
                $aAnilistEnable = [];
                foreach ($request->variable_names() as $k => $varname)
                {
                    preg_match('/anilist-(\d+)/', $varname, $match_anilist_toggle);
                    if ($match_anilist_toggle)
                    {
                        $gid = $match_anilist_toggle[1];
                        $var = "anilist-$gid";
                        $var = $request->variable($var, '0');
                        $aAnilistEnable[$gid] = $var ? 1 : 0;
                    }
                }
                $sql = 'UPDATE ' . GROUPS_TABLE . 
                    buildSqlSetCase('group_id', 'snp_anilist_enable', $aAnilistEnable);
                $db->sql_query($sql);
                // Code to limit googlebooks based on group membership
                $aGooglebooksEnable = [];
                foreach ($request->variable_names() as $k => $varname)
                {
                    preg_match('/googlebooks-(\d+)/', $varname, $match_googlebooks_toggle);
                    if ($match_googlebooks_toggle)
                    {
                        $gid = $match_googlebooks_toggle[1];
                        $var = "googlebooks-$gid";
                        $var = $request->variable($var, '0');
                        $aGooglebooksEnable[$gid] = $var ? 1 : 0;
                    }
                }
                $sql = 'UPDATE ' . GROUPS_TABLE . 
                    buildSqlSetCase('group_id', 'snp_googlebooks_enable', $aGooglebooksEnable);
                $db->sql_query($sql);
                // Code to limit gamespot based on group membership
                $aGamespotEnable = [];
                foreach ($request->variable_names() as $k => $varname)
                {
                    preg_match('/gamespot-(\d+)/', $varname, $match_gamespot_toggle);
                    if ($match_gamespot_toggle)
                    {
                        $gid = $match_gamespot_toggle[1];
                        $var = "gamespot-$gid";
                        $var = $request->variable($var, '0');
                        $aGamespotEnable[$gid] = $var ? 1 : 0;
                    }
                }
                $sql = 'UPDATE ' . GROUPS_TABLE . 
                    buildSqlSetCase('group_id', 'snp_gamespot_enable', $aGamespotEnable);
                $db->sql_query($sql);
                // Store Forum IDs from template
                foreach ($pg_names as $pg_name)
                {
                    $config->set('snp_pg_fid_' . $pg_name, sanitize_fid($request->variable($pg_name . '_fid', '')));
                }
                // show status screen
                meta_refresh(2, $this->u_action);
                trigger_error($user->lang('ACP_SNP_SETTING_SAVED') . adm_back_link($this->u_action));
            }
            $template->assign_vars(array(
                'U_ACTION'				=> $this->u_action,
            ));
            // Code to show imdb configuration in ACP
            $sql = 'SELECT * from ' . GROUPS_TABLE;
            $result = $db->sql_query($sql);
            while ($row = $db->sql_fetchrow($result))
            {
                $group = array(
                    'gid'=>$row['group_id'],
                    'gname'=>$row['group_name'],
                    'gcheck'=> $row['snp_imdb_enable'],
                );
                $template->assign_block_vars('aImdb', $group);
            };
            $db->sql_freeresult($result);
            // Code to show anilist configuration in ACP
            $sql = 'SELECT * from ' . GROUPS_TABLE;
            $result = $db->sql_query($sql);
            while ($row = $db->sql_fetchrow($result))
            {
                $group = array(
                    'gid'=>$row['group_id'],
                    'gname'=>$row['group_name'],
                    'gcheck'=> $row['snp_anilist_enable'],
                );
                $template->assign_block_vars('aAnilist', $group);
            };
            $db->sql_freeresult($result);
            // Code to show googlebooks configuration in ACP
            $sql = 'SELECT * from ' . GROUPS_TABLE;
            $result = $db->sql_query($sql);
            while ($row = $db->sql_fetchrow($result))
            {
                $group = array(
                    'gid'=>$row['group_id'],
                    'gname'=>$row['group_name'],
                    'gcheck'=> $row['snp_googlebooks_enable'],
                );
                $template->assign_block_vars('aGooglebooks', $group);
            };
            $db->sql_freeresult($result);
            // Code to show gamespot configuration in ACP
            $sql = 'SELECT * from ' . GROUPS_TABLE;
            $result = $db->sql_query($sql);
            while ($row = $db->sql_fetchrow($result))
            {
                $group = array(
                    'gid'=>$row['group_id'],
                    'gname'=>$row['group_name'],
                    'gcheck'=> $row['snp_gamespot_enable'],
                );
                $template->assign_block_vars('aGamespot', $group);
            };
            $db->sql_freeresult($result);

            // To fill the forum id for the textarea
            foreach ($pg_names as $pg_name)
            {
                $fid = $config['snp_pg_fid_' . $pg_name];
                $template->assign_var($pg_name . '_fid', $fid);
            }
        }
    }

    public function handle_donation($cfg)
    {
		global $config, $request, $template, $user, $db, $phpbb_container;
        $tpl_name = $cfg['tpl_name'];
        if ($tpl_name)
        {
            $this->tpl_name = $tpl_name;
            add_form_key('jeb_snp');
            if ($request->is_set_post('submit'))
            {
                $phpbb_notifications = $phpbb_container->get('notification_manager');
                if (!check_form_key('jeb_snp'))
                {
                    trigger_error('FORM_INVALID', E_USER_WARNING);
                }
                $config->set('snp_don_b_show_navlink', $request->variable('b_show_navlink', 0));
                $config->set('snp_don_url', $request->variable('don_url', ''));
                trigger_error($user->lang('ACP_SNP_SETTING_SAVED') . adm_back_link($this->u_action));
            }
            $template->assign_vars(array(
                'B_SHOW_NAVLINK'                => $config['snp_don_b_show_navlink'],
                'DON_URL'                       => $config['snp_don_url'],
                'b_snahp_notify_checked'        => $config['snp_b_snahp_notify'],
                'b_notify_on_poke_checked'      => $config['snp_b_notify_on_poke'],
                'b_notify_op_on_report_checked' => $config['snp_b_notify_op_on_report'],
                'U_ACTION'                      => $this->u_action,
            ));
        }
    }

}
