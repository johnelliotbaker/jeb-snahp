<?php
namespace jeb\snahp\Apps\Core\Exception;

class UserDoesNotExist extends \Exception
{
    public function __construct($message = '', $code = 0, $previous = null) {
        $message = 'User not found.';
        parent::__construct($message, $code, $previous);
    }
}
