<?php
namespace jeb\snahp\Apps\UserRestrictor;

class UserRestrictorHelper
{
    const CACHE_DURATION = 0;
    const CACHE_DURATION_LONG = 0;

    protected $db;
    protected $user;
    protected $template;
    protected $tbl;
    protected $sauth;
    public function __construct($db, $user, $template, $tbl, $sauth)
    {
        $this->db = $db;
        $this->user = $user;
        $this->template = $template;
        $this->tbl = $tbl;
        $this->rxnTbl = $tbl["UserRestrictor"];
        $this->sauth = $sauth;
        $this->userId = $sauth->userId;
    }

    public function restrictUser($userId)
    {
        $userId = (int) $userId;
        if (!$this->getUserData($userId)) {
            throw new \Exception(
                "That user does not exist. Error Code: 5b6878e434"
            );
        }
        $data = ["snp_restricted" => 1];
        $sql =
            "UPDATE " .
            USERS_TABLE .
            " SET " .
            $this->db->sql_build_array("UPDATE", $data) .
            " WHERE user_id=$userId";
        $this->db->sql_query($sql);
    }

    public function freeUser($userId)
    {
        $userId = (int) $userId;
        if (!$this->getUserData($userId)) {
            throw new \Exception(
                "That user does not exist. Error Code: 5b6878e434"
            );
        }
        $data = ["snp_restricted" => 0];
        $sql =
            "UPDATE " .
            USERS_TABLE .
            " SET " .
            $this->db->sql_build_array("UPDATE", $data) .
            " WHERE user_id=$userId";
        $this->db->sql_query($sql);
    }

    public function getUserData($userId)
    {
        $userId = (int) $userId;
        $sql =
            "SELECT user_id, username, snp_restricted FROM " .
            USERS_TABLE .
            " WHERE user_id=$userId";
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        return $row;
    }
}
