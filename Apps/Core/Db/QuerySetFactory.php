<?php
namespace jeb\snahp\Apps\Core\Db;

require_once "/var/www/forum/ext/jeb/snahp/Apps/Core/Db/QuerySet.php";

class QuerySetFactory
{
    public function __construct($db)
    {
        $this->db = $db;
    }

    public function fromSqlArray($sqlArray)
    {
        return new QuerySet($this->db, $sqlArray);
    }
}
