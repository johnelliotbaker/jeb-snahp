<?php

namespace jeb\snahp\Apps\Core\Db\query;

trait BaseQueryMixin
{
    public function update($id, $data)
    {
        $id = (int) $id;
        $idString = $this->ID_STRING;
        $sql =
            "UPDATE " .
            $this->OWN_TABLE_NAME .
            '
            SET ' .
            $this->db->sql_build_array("UPDATE", $data) .
            "
            WHERE ${idString}=${id}";
        $this->db->sql_query($sql);
    }

    public function get($id, $options = null)
    {
        $fields = $options["fields"] ?? ["*"];

        $id = (int) $id;
        $sqlArray = [
            "SELECT" => $this->makeFieldString($fields, "a"),
            "FROM" => [$this->OWN_TABLE_NAME => "a"],
            "WHERE" => $this->ID_STRING . "=${id}",
        ];
        $sql = $this->db->sql_build_query("SELECT", $sqlArray);
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        return $row;
    }

    public function getOrRaiseException($id, $options = null)
    {
        $instance = $this->get($id, $options);
        if (!$instance) {
            if (isset($options["exception"])) {
                throw new $options["exception"]();
            }
            throw new \Exception("Could not get. Error Code: 01f6e3f066");
        }
        return $instance;
    }

    public function where($where, $options = null)
    {
        $fields = $options["fields"] ?? ["*"];
        $sqlArray = [
            "SELECT" => $this->makeFieldString($fields, "a"),
            "FROM" => [$this->OWN_TABLE_NAME => "a"],
            "WHERE" => $where,
        ];
        $sql = $this->db->sql_build_query("SELECT", $sqlArray);
        $result = $this->db->sql_query($sql);
        $many = $options["many"] ?? true;
        if ($many) {
            $res = $this->db->sql_fetchrowset($result);
        } else {
            $res = $this->db->sql_fetchrow($result);
        }
        $this->db->sql_freeresult($result);
        return $res;
    }

    public function makeFieldString($fields, $tablename = null)
    {
        if (is_string($fields)) {
            $fields = explode(",", $fields);
        }
        if ($tablename) {
            $fields = array_map(function ($field) use ($tablename) {
                return "$tablename.$field";
            }, $fields);
        }
        return implode(", ", $fields);
    }
}
