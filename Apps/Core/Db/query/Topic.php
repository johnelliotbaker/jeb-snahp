<?php
namespace jeb\snahp\Apps\Core\Db\query;

class Topic
{
    public function __construct($db)
    {
        $this->db = $db;
    }

    public function get($topicId)
    {
        $sqlArray = [
            'SELECT' => '*',
            'FROM' => [TOPICS_TABLE => 'a'],
            'WHERE' => "topic_id=${topicId}",
        ];
        $sql = $this->db->sql_build_query('SELECT', $sqlArray);
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        return $row;
    }

    public function update($id, $data)
    {
        $id = (int) $id;
        $sql = 'UPDATE ' . TOPICS_TABLE . '
            SET ' . $this->db->sql_build_array('UPDATE', $data) . "
            WHERE topic_id=${id}";
        $this->db->sql_query($sql);
    }
}
