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
            $this->handle_mode($cfg);
        }
	}

    public function handle_mode($cfg)
    {
		global $config, $request, $template, $user;
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
}
