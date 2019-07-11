<?php

namespace jeb\snahp\controller;

use jeb\snahp\core\base;

class emotes extends base
{

    protected $base_url = '';

    public function __construct()/*{{{*/
    {
    }/*}}}*/

	public function handle($mode)/*{{{*/
	{
        $this->reject_anon();
        $this->tbl = $this->container->getParameter('jeb.snahp.tables');
        $this->lut = $this->container->getParameter('jeb.snahp.emotes');
        $this->user_id = $this->user->data['user_id'];
        switch ($mode)
        {
        case 'ls':
            $cfg['tpl_name'] = '';
            $cfg['b_feedback'] = false;
            return $this->ls($cfg);
            break;
        default:
            trigger_error('Error Code: f8c1f1638f');
            break;
        }
	}/*}}}*/

    public function ls($cfg)/*{{{*/
    {
        return $this->listings_json($cfg);
    }/*}}}*/

    private function get_emote_digest()/*{{{*/
    {
        $lut = $this->lut;
        $data = [];
        $group_id = $this->user->data['group_id'];
        $group_data = $this->select_group($group_id);
        $allowed_types = unserialize($group_data['snp_emo_allowed_types']);
        foreach($lut as $key=>$entry)
        {
            if (in_array($entry['type'], $allowed_types))
            {
                $data[$key] = $entry;
            }
        }
        return $data;
    }/*}}}*/

    private function listings_json($cfg)/*{{{*/
    {
        $js = new \phpbb\json_response();
        $data = $this->get_emote_digest();
        $status = 0;
        if (!$data)
        {
            $status = -1;
        }
        $js->send([
            'status' => $status,
            'data' => $data,
        ]);
    }/*}}}*/


}
