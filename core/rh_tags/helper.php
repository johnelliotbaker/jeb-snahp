<?php
namespace jeb\snahp\core\rh_tags;

class helper
{
    protected $db;
    protected $user;
    protected $request;
    protected $container;
    protected $sauth;
    protected $this_user_id;
    public function __construct($db, $user, $request, $container, $tbl, $sauth)
    {
        $this->db = $db;
        $this->user = $user;
        $this->request = $request;
        $this->container = $container;
        $this->tbl = $tbl;
        $this->sauth = $sauth;
        $this->user_id = $this->user->data["user_id"];
        $this->block_data = [];
    }

    public function get_tagnames_from_group_map($groupname, $cd = 30)
    {
        $sql_array = [
            "SELECT" => "tagname",
            "FROM" => [$this->tbl["tags_group_map"] => "a"],
            "WHERE" => "groupname='${groupname}'",
        ];
        $sql = $this->db->sql_build_query("SELECT", $sql_array);
        // $result = $this->db->sql_query($sql, $cd);
        $result = $this->db->sql_query($sql);
        $rowset = $this->db->sql_fetchrowset($result);
        $this->db->sql_freeresult($result);
        return array_map(function ($arg) {
            return $arg["tagname"];
        }, $rowset);
    }

    public function get_groupnames_from_forum_map($forum_id, $cd = 30)
    {
        $forum_id = (int) $forum_id;
        if (!$forum_id) {
            return [];
        }
        $sql_array = [
            "SELECT" => "groupname",
            "FROM" => [$this->tbl["tags_forum_map"] => "a"],
            "WHERE" => "forum_id=${forum_id}",
            "ORDER_BY" => "priority DESC",
        ];
        $sql = $this->db->sql_build_query("SELECT", $sql_array);
        // $result = $this->db->sql_query($sql, $cd);
        $result = $this->db->sql_query($sql);
        $rowset = $this->db->sql_fetchrowset($result);
        $this->db->sql_freeresult($result);
        return array_map(function ($arg) {
            return $arg["groupname"];
        }, $rowset);
    }

    public function insert_or_update_group_map($data)
    {
        $sql =
            "INSERT INTO " .
            $this->tbl["tags_group_map"] .
            $this->db->sql_build_array("INSERT", $data) .
            '
            ON DUPLICATE KEY UPDATE ' .
            $this->db->sql_build_array("UPDATE", $data);
        $this->db->sql_query($sql);
    }

    public function insert_or_update_forum_map($data)
    {
        $sql =
            "INSERT INTO " .
            $this->tbl["tags_forum_map"] .
            $this->db->sql_build_array("INSERT", $data) .
            '
            ON DUPLICATE KEY UPDATE ' .
            $this->db->sql_build_array("UPDATE", $data);
        $this->db->sql_query($sql);
    }

    public function delete_forum_map($forum_id)
    {
        $forum_id = (int) $forum_id;
        $sql =
            "DELETE FROM " .
            $this->tbl["tags_forum_map"] .
            " WHERE forum_id=${forum_id}";
        $this->db->sql_query($sql);
    }

    public function delete_group_map($groupname, $tagnames)
    {
        $groupname = $this->db->sql_escape($groupname);
        $sql_in_set = $this->db->sql_in_set("tagname", $tagnames);
        $sql =
            "DELETE FROM " .
            $this->tbl["tags_group_map"] .
            " WHERE groupname='${groupname}' AND $sql_in_set";
        $this->db->sql_query($sql);
    }
}
