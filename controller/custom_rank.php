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
        if (!$this->config['snp_ucp_custom_b_master'])
        {
            trigger_error('Custom settings are disabled. Error Code: 14c96e703c');
        }
        $user_id = $this->user->data['user_id'];
        // $this->reject_user_not_in_groupset($user_id, 'Red Team');
        $cfg = [];
        switch ($mode)
        {
        case 'save':
            $cfg['tpl_name'] = '';
            $cfg['b_feedback'] = false;
            return $this->save($cfg);
            break;
        case 'get_info':
            return $this->respond_info_as_json();
        default:
            trigger_error('Invalid request category. Error Code: 57f43ea934');
            break;
        }
	}/*}}}*/

    public function respond_info_as_json()/*{{{*/
    {
        $js = new \phpbb\json_response();
        $user_id = $this->user->data['user_id'];
        $row = $this->get_custom_rank($user_id);
        return $js->send($row);
    }/*}}}*/

    private function truncate_string($strn, $available)/*{{{*/
    {
        $array = str_split($strn);
        $count = 0;
        foreach($array as $letter)
        {
            if(mb_detect_encoding($letter)=='UTF-8')
            {
                $available -= 2.7;
            }
            else
            {
                $available -= 1;
            }
            $count += 1;
            if ($available <= 0)
            {
                break;
            }
        }
        return mb_substr($strn, 0, $count);
    }/*}}}*/

    public function save($cfg)/*{{{*/
    {
        $js = new \phpbb\json_response();
        $rank_title = $this->db->sql_escape($this->request->variable('rt', '', true));
        $rank_title = $this->truncate_string($rank_title, 22);
        $rank_img = $this->db->sql_escape($this->request->variable('ri', ''));
        $user_id = (int) $this->user->data['user_id'];
        $b_success = $this->set_custom_rank($user_id, $rank_title, $rank_img);
        if ($b_success)
        {
            return $js->send(['status'=> 1, 'reason' => '']);
        }
        return $js->send(['status'=> 0, 'reason' => 'Error Code: fbe7eb9944']);
    }/*}}}*/

}
