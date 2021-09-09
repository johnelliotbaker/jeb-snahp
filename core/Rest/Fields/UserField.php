<?php

namespace jeb\snahp\core\Rest\Fields;

class UserField
{
    public function __construct($options = [])
    {
        $this->default = array_key_exists("default", $options)
            ? $options["default"]
            : null;
    }

    public function validate($data)
    {
        return $data;
    }

    public function serialize($userId)
    {
        return getUserInfo((int) $userId);
    }
}

function getUserInfo($userId)
{
    global $db;
    $sql =
        "SELECT username, user_colour FROM " .
        USERS_TABLE .
        " WHERE user_id=${userId}";
    $result = $db->sql_query($sql);
    $row = $db->sql_fetchrow($result);
    $db->sql_freeresult($result);
    return $row;
}
