<?php

namespace jeb\snahp\controller;

use jeb\snahp\core\base;

class custom_rank extends base
{

    public function __construct()/*{{{*/
    {
    }/*}}}*/

	public function handle($mode)/*{{{*/
	{
        $this->reject_anon();
        if (!$this->config['snp_ucp_custom_b_master'])
        {
            trigger_error('Custom settings are disabled. Error Code: 14c96e703c');
        }
        $cfg = [];
        switch ($mode)
        {
        case 'save':
            $cfg['tpl_name'] = '';
            $cfg['b_feedback'] = false;
            return $this->save($cfg);
            break;
        default:
            trigger_error('Invalid request category. Error Code: 57f43ea934');
            break;
        }
	}/*}}}*/

    public function save($cfg)/*{{{*/
    {
        $js = new \phpbb\json_response();
        $rank_title = $this->db->sql_escape($this->request->variable('rt', ''));
        $rank_img = $this->db->sql_escape($this->request->variable('ri', ''));
        $rank_title = substr($rank_title, 0, 22);
        $user_id = (int) $this->user->data['user_id'];
        $b_success = $this->set_custom_rank($user_id, $rank_title, $rank_img);
        if ($b_success)
        {
            return $js->send(['status'=> 1, 'reason' => '']);
        }
        return $js->send(['status'=> 0, 'reason' => 'Error Code: fbe7eb9944']);
    }/*}}}*/

}
