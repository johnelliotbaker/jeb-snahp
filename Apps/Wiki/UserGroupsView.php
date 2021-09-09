<?php

namespace jeb\snahp\Apps\Wiki;

use Symfony\Component\HttpFoundation\JsonResponse;
use \R as R;

class UserGroupsView
{
    protected $request;
    protected $sauth;
    public function __construct($db, $request, $sauth)
    {
        $this->db = $db;
        $this->request = $request;
        $this->sauth = $sauth;
        $this->sauth->reject_anon("Error Code: cee5910f65");
    }

    public function view()
    {
        if (!$this->sauth->is_dev()) {
            throw new \Exception(
                "You do not have the permission to access this page. Error Code: 08c20676d9"
            );
        }

        $sql =
            "SELECT group_id, group_name from " .
            GROUPS_TABLE .
            " ORDER BY group_id ASC";
        $result = $this->db->sql_query($sql);
        $rowset = $this->db->sql_fetchrowset($result);
        $this->db->sql_freeresult($result);
        return new JsonResponse($rowset);
    }
}
