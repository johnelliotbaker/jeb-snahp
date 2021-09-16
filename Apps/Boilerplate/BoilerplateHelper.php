<?php
namespace jeb\snahp\Apps\Boilerplate;

// use phpbb\db\driver\driver_interface;
// use jeb\snahp\Apps\Core\Db\QuerySetFactory;
// use jeb\snahp\Apps\Core\Pagination\PageNumberPagination;
// use jeb\snahp\core\auth\user_auth;

class BoilerplateHelper
{
    public $db;
    public $sauth;
    public $pageNumberPagination;
    public $QuerySetFactory;
    public function __construct(
        $db,
        $tbl,
        $sauth,
        $pageNumberPagination,
        $QuerySetFactory
    ) {
        $this->db = $db;
        $this->tbl = $tbl;
        $this->sauth = $sauth;
        $this->paginator = $pageNumberPagination;
        $this->QuerySetFactory = $QuerySetFactory;
        $this->userId = $sauth->userId;
    }
}
