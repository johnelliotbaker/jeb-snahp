<?php
namespace jeb\snahp\core\handlebar;

class helper
{
    protected $db;
    protected $auth;
    protected $user;
    protected $container;
    protected $this_user_id;
    public function __construct($container)
    {
        $this->container = $container;
    }

    public function repl($message)
    {
        $ptn = "/\|\|([^|]+)\|\|/";
        $message = preg_replace_callback(
            $ptn,
            function ($arg) {
                $text = htmlspecialchars($arg[1]);
                return "<span id=\"rx-test\" class=\"rx-test\" data-text=\"$text\" />";
            },
            $message
        );
        return $message;
    }
}
