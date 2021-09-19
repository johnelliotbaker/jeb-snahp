<?php
namespace jeb\snahp\Apps\Core\Db\query;

require_once "/var/www/forum/ext/jeb/snahp/Apps/Core/Db/query/Mixins.php";

class User
{
    use BaseQueryMixin;

    public function __construct($db)
    {
        $this->db = $db;
        $this->OWN_TABLE_NAME = USERS_TABLE;
        $this->ID_STRING = "user_id";
    }

    public function getWithUsername($username)
    {
        $username = utf8_clean_string($this->db->sql_escape($username));
        return $this->where("username='$username'", ["many" => false]);
    }
}
