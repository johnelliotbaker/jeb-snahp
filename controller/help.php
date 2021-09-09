<?php

namespace jeb\snahp\controller;

use jeb\snahp\core\base;

class help extends base
{
    protected $base_url = '';

    public function __construct()
    {
    }/*}}}*/

    public function handle($mode)
    {
        $this->reject_anon();
        $this->tbl = $this->container->getParameter('jeb.snahp.tables');
        $this->user_id = $this->user->data['user_id'];
        switch ($mode) {
        case 'docs':
            $cfg['tpl_name'] = '';
            $cfg['b_feedback'] = false;
            return $this->docs($cfg);
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
    }/*}}}*/

    public function docs($cfg)
    {
        return $this->show_docs($cfg);
    }/*}}}*/

    private function show_docs($cfg)
    {
        $name = $this->request->variable('name', '');
        switch ($name) {
        case 'ucp_custom_rank':
            return $this->helper->render('@jeb_snahp/help/component/ucp/component/custom_rank/base.html');
        case 'digg_digg':
            return $this->helper->render('@jeb_snahp/help/component/digg/component/digg/base.html');
        case 'digg_register':
            return $this->helper->render('@jeb_snahp/help/component/digg/component/register/base.html');
        }
    }/*}}}*/
}
