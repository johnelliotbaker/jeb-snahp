<?php
namespace jeb\snahp\core\errors;

class SnahpException extends \Exception
{
    const MESSAGE_PREFIX = "Default Snahp Exception";
    public function __construct($message = "", $code = 0, $delim = " ")
    {
        $msg = $this::MESSAGE_PREFIX;
        if ($message) {
            $msg .= $delim . $message;
        }
        parent::__construct($msg, $code);
    }
}
