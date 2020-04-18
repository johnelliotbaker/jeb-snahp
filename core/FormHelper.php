<?php
namespace jeb\snahp\core;

class FormHelper
{
    protected $request;
    protected $template;
    public function __construct(/*{{{*/
        $request,
        $template
    ) {
        $this->request = $request;
        $this->template = $template;
    }/*}}}*/

    public function getRequestVars($varDict)
    {
        $res = [];
        foreach ($varDict as $varname=>$default) {
            $res[$varname] = $this->request->variable($varname, $default);
        }
        return $res;
    }

    public function setTemplateVars($vars)
    {
        foreach ($vars as $varname=>$value) {
            $varname = strtoupper($varname);
            $this->template->assign_var($varname, $value);
        }
    }
}
