<?php
namespace jeb\snahp\Apps\Core\Db\query;

require_once "/var/www/forum/ext/jeb/snahp/Apps/Core/Db/query/Mixins.php";

class BaseQuery
{
    use BaseQueryMixin;

    public function __construct($db)
    {
        $this->db = $db;
        // $this->OWN_TABLE_NAME = BASEQUERYS_TABLE;
        // $this->ID_STRING = "baseQuery_id";
    }
}
