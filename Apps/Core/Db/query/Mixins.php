<?php

namespace jeb\snahp\Apps\Core\Db\query;

trait BaseQueryMixin
{
    public function update($id, $data)
    {
        $id = (int) $id;
        $idString = $this->ID_STRING;
        $sql = 'UPDATE ' . $this->OWN_TABLE_NAME . '
            SET ' . $this->db->sql_build_array('UPDATE', $data) . "
            WHERE ${idString}=${id}";
        $this->db->sql_query($sql);
    }

    public function get($id, $options=null)
    {
        $fields = $options['fields'] ?? ['*'];

        $id = (int) $id;
        $sqlArray = [
            'SELECT' => $this->makeFieldString($fields, 'a'),
            'FROM' => [$this->OWN_TABLE_NAME => 'a'],
            'WHERE' => $this->ID_STRING . "=${id}",
        ];
        $sql = $this->db->sql_build_query('SELECT', $sqlArray);
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        return $row;
    }

    public function makeFieldString($fields, $tablename=null)
    {
        if (is_string($fields)) {
            $fields = explode(',', $fields);
        }
        if ($tablename) {
            $fields = array_map(
                function ($field) use ($tablename) {
                    return "$tablename.$field";
                },
                $fields
            );
        }
        return implode(', ', $fields);
    }
}
