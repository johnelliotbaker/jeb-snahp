<?php

namespace jeb\snahp\controller;

use \Symfony\Component\HttpFoundation\Response;
use jeb\snahp\core\base;
use jeb\snahp\core\bank_helper;

class bank extends base
{
    protected $prefix;
    protected $bank_helper;

    public function __construct($prefix, bank_helper $helper)
    {
        $this->prefix = $prefix;
        $this->bank_helper = $helper;
    }

    public function handle($mode)
    {
        $this->reject_anon();
        switch ($mode) {
        case 'test':
            $cfg['tpl_name'] = '';
            $cfg['b_feedback'] = false;
            return $this->test($cfg);
            break;
        case 'ls':
            $cfg['tpl_name'] = '';
            $cfg['b_feedback'] = false;
            return $this->ls($cfg);
            break;
        default:
            trigger_error('Error Code: 85d7d3d7a2');
            break;
        }
    }

    public function test($cfg)
    {
        $this->bank_helper->exchange('invitation_points', 'sell', 10);
        // $this->bank_helper->withdraw();
        // $this->bank_helper->exchange();
        $js = new \phpbb\json_response();
        return $js->send([]);
    }
}
