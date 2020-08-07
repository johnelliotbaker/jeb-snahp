<?php

namespace jeb\snahp\core\Rest;

define('REDBEAN_MODEL_PREFIX', 'jeb\\snahp\\Apps\\MiniBoard\\Models\\');

// require_once 'ext/jeb/snahp/core/RedBean/rb.php';
try {
    include_once 'ext/jeb/snahp/core/RedBean/rb.php';
} catch (Exception $e) {
}

use \R as R;

# DEFINES ARE MADE IN MiniBoardEventListener.php because that loads first

trait RedBeanSetup
{
    public function connectDatabase($frozen=true)
    {
        global $phpbb_root_path, $phpEx;
        include $phpbb_root_path . 'config.' . $phpEx;
        R::setup(
            "mysql:host=localhost;dbname=${dbname}",
            $dbuser,
            $dbpasswd
        );
        R::freeze($frozen);
        R::ext(
            'xdispense',
            function ($type) {
                return R::getRedBean()->dispense($type);
            }
        );
    }
}
