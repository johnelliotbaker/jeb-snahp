<?php

/**
 * undocumented function
 *
 * @return void
 */
namespace jeb\snahp\core;

use phpbb\template\context;
use phpbb\user;


abstract class core
{
    protected $user;
    protected $template_context;

    public function prn($var) {
        if (is_array($var))
        { foreach ($var as $k => $v) { echo "$k => "; prn($v); }
        } else { echo "$var<br>"; }
    }

    public function set_user(user $user)
    {
        $this->user = $user;
    }

    public function set_template_context(context $ctx)
    {
        $this->template_context = $ctx;
    }

}

