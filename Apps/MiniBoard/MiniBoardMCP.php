<?php

namespace jeb\snahp\Apps\MiniBoard;

class MiniBoardMCP
{
    protected $request;

    public function __construct($request, $template, $phpHelper, $sauth)
    {
        $this->request = $request;
        $this->template = $template;
        $this->phpHelper = $phpHelper;
        $this->sauth = $sauth;
        $sauth->reject_non_dev('Error Code: 6d14c8fe2c');
    }

    public function manage()/*{{{*/
    {
        $cfg['tpl_name'] = '@jeb_snahp/mini_board/mcp/base.html';
        $cfg['title'] = 'Manage Mini Board';
        $miniforum = \R::find(MINIFORUMS_TABLE);
        $this->template->assign_var('data', $miniforum);
        return $this->phpHelper->render($cfg['tpl_name'], $cfg['title']);
    }/*}}}*/
}
