<?php
namespace jeb\snahp\Apps\Core\Db\query;

require_once "/var/www/forum/ext/jeb/snahp/Apps/Core/Db/query/Mixins.php";
require_once "/var/www/forum/ext/jeb/snahp/Apps/Core/Exception/UserDoesNotExist.php";

use jeb\snahp\Apps\Core\Exception as Exception;

class User
{
    use BaseQueryMixin {
        getOrRaiseException as traitGetOrRaiseException;
    }

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

    public function getOrRaiseException($id, $options = null)
    {
        if ($options === null) {
            $options = ["exception" => Exception\UserDoesNotExist::class];
        } else {
            if (is_array($options)) {
                $options["exception"] = Exception\UserDoesNotExist::class;
            }
        }
        return $this->traitGetOrRaiseException($id, $options);
    }
}
