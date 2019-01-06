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
        case 'signature':
            $cfg['tpl_name'] = 'acp_snp_signature';
            $cfg['b_feedback'] = false;
            $this->handle_signature($cfg);
            break;
        case 'imdb':
            $cfg['tpl_name'] = 'acp_snp_imdb';
            $cfg['b_feedback'] = false;
            $this->handle_imdb($cfg);
            break;
        case 'settings':
            $cfg['tpl_name'] = 'acp_snp_settings';
            $cfg['b_feedback'] = false;
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

    public function handle_default($cfg)
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
                trigger_error($user->lang('ACP_SNP_SETTING_SAVED') . adm_back_link($this->u_action));
            }
            $template->assign_vars(array(
                'U_ACTION'				=> $this->u_action,
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
                    preg_match("/sig-(\d+)/", $varname, $match_sig_toggle);
                    if ($match_sig_toggle)
                    {
                        $gid = $match_sig_toggle[1];
                        $var = "sig-$gid";
                        $var = $request->variable($var, "0");
                        $aSigEnable[$gid] = $var ? 1 : 0;
                    }
                    preg_match("/sigrows-(\d+)/", $varname, $match_sig_rows);
                    if ($match_sig_rows)
                    {
                        $gid = $match_sig_rows[1];
                        $sid = "sigrows-$gid";
                        $var = $request->variable($sid, "0");
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
            $sql = "SELECT * from " . GROUPS_TABLE;
            $result = $db->sql_query($sql);
            while ($row = $db->sql_fetchrow($result))
            {
                $group = array(
                    "gid"=>$row["group_id"],
                    "gname"=>$row["group_name"],
                    "gcheck"=> $row["snp_enable_signature"],
                    "grows"=> $row["snp_signature_rows"],
                );
                $template->assign_block_vars('aSignature', $group);
            };
            $db->sql_freeresult($result);
        }
    }

    public function handle_imdb($cfg)
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
                // Code to limit IMDb based on group membership
                $aImdbEnable = [];
                foreach ($request->variable_names() as $k => $varname)
                {
                    preg_match("/imdb-(\d+)/", $varname, $match_imdb_toggle);
                    if ($match_imdb_toggle)
                    {
                        $gid = $match_imdb_toggle[1];
                        $var = "imdb-$gid";
                        $var = $request->variable($var, "0");
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
                    preg_match("/anilist-(\d+)/", $varname, $match_anilist_toggle);
                    if ($match_anilist_toggle)
                    {
                        $gid = $match_anilist_toggle[1];
                        $var = "anilist-$gid";
                        $var = $request->variable($var, "0");
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
                    preg_match("/googlebooks-(\d+)/", $varname, $match_googlebooks_toggle);
                    if ($match_googlebooks_toggle)
                    {
                        $gid = $match_googlebooks_toggle[1];
                        $var = "googlebooks-$gid";
                        $var = $request->variable($var, "0");
                        $aGooglebooksEnable[$gid] = $var ? 1 : 0;
                    }
                }
                $sql = 'UPDATE ' . GROUPS_TABLE . 
                    buildSqlSetCase('group_id', 'snp_googlebooks_enable', $aGooglebooksEnable);
                $db->sql_query($sql);
                trigger_error($user->lang('ACP_SNP_SETTING_SAVED') . adm_back_link($this->u_action));
            }
            $template->assign_vars(array(
                'U_ACTION'				=> $this->u_action,
            ));
            // Code to show imdb configuration in ACP
            $sql = "SELECT * from " . GROUPS_TABLE;
            $result = $db->sql_query($sql);
            while ($row = $db->sql_fetchrow($result))
            {
                $group = array(
                    "gid"=>$row["group_id"],
                    "gname"=>$row["group_name"],
                    "gcheck"=> $row["snp_imdb_enable"],
                );
                $template->assign_block_vars('aImdb', $group);
            };
            $db->sql_freeresult($result);
            // Code to show anilist configuration in ACP
            $sql = "SELECT * from " . GROUPS_TABLE;
            $result = $db->sql_query($sql);
            while ($row = $db->sql_fetchrow($result))
            {
                $group = array(
                    "gid"=>$row["group_id"],
                    "gname"=>$row["group_name"],
                    "gcheck"=> $row["snp_anilist_enable"],
                );
                $template->assign_block_vars('aAnilist', $group);
            };
            $db->sql_freeresult($result);
            // Code to show googlebooks configuration in ACP
            $sql = "SELECT * from " . GROUPS_TABLE;
            $result = $db->sql_query($sql);
            while ($row = $db->sql_fetchrow($result))
            {
                $group = array(
                    "gid"=>$row["group_id"],
                    "gname"=>$row["group_name"],
                    "gcheck"=> $row["snp_googlebooks_enable"],
                );
                $template->assign_block_vars('aGooglebooks', $group);
            };
            $db->sql_freeresult($result);
        }
    }
}
