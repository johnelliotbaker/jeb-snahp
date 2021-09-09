<?php

namespace jeb\snahp\controller;

use jeb\snahp\core\base;

class mdl extends base
{

    protected $base_url = '';

    public function __construct()
    {
    }

	public function handle($mode)
	{
        $this->reject_anon();
        $this->tbl = $this->container->getParameter('jeb.snahp.tables');
        $this->user_id = $this->user->data['user_id'];
        switch ($mode)
        {
        case 'title':
            $cfg['tpl_name'] = '';
            $cfg['b_feedback'] = false;
            return $this->title($cfg);
            break;
        case 'search':
            $cfg['tpl_name'] = '';
            $cfg['b_feedback'] = false;
            return $this->search($cfg);
            break;
        default:
            trigger_error('Error Code: 12e1ae39b6');
            break;
        }
	}

    public function title($cfg)
    {
        $this->title_json($cfg);
    }

    public function title_json($cfg)
    {
        $js = new \phpbb\json_response();
        $id = $this->request->variable('id', 0);
        if (!$id)
        {
            $js->send(['status' => 'Error Code: 5acbebee45']);
        }
        $url = "https://api.mydramalist.com/v1/titles/{$id}";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'mdl-api-key: 6e820ce645f4988dc5ec802984bba446dc1668c7'
        ]);
        $result = curl_exec($ch);
		$js->send($result);
        trigger_error($result);
    }

    public function search($cfg)
    {
        $this->search_json($cfg);
    }

    public function search_json($cfg)
    {
        $js = new \phpbb\json_response();
        $title = $this->request->variable('title', '');
        if (!$title)
        {
            $js->send(['status' => 'Error Code: d64c7f38fc']);
        }
        $url = "https://api.mydramalist.com/v1/search/titles?q={$title}";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'mdl-api-key: 6e820ce645f4988dc5ec802984bba446dc1668c7'
        ]);
        $result = curl_exec($ch);
		$js->send($result);
        trigger_error($result);
    }

}
