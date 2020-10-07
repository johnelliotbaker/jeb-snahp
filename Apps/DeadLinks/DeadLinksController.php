<?php

namespace jeb\snahp\Apps\DeadLinks;

class DeadLinksController
{
    protected $phpHelper;
    protected $sauth;
    public function __construct(
        $phpHelper,
        $sauth
    ) {
        $this->phpHelper = $phpHelper;
        $this->sauth = $sauth;
        $this->userId = (int) $this->user->data['user_id'];
        $this->sauth->reject_new_users('Error Code: 0beb743101');
    }

    public function view()/*{{{*/
    {
        $cfg['tpl_name'] = '@jeb_snahp/deadlinks/rx_deadlinks.html';
        $cfg['title'] = 'Deadlinks Reporter';
        return $this->phpHelper->render($cfg['tpl_name'], $cfg['title']);
    }/*}}}*/
}
