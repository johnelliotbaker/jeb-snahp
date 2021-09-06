<?php
namespace jeb\snahp\Apps\Core\Db\query;

class Request
{
    public function __construct($db, $tbl)
    {
        $this->db = $db;
        $this->tbl = $tbl;
    }

    public function getWithTopicId($topicId)/*{{{*/
    {
        $topicId = (int) $topicId;
        $sqlArray = [
            'SELECT' => '*',
            'FROM' => [ $this->tbl['req'] => 'a'],
            'WHERE' => "tid=${topicId}",
        ];
        $sql = $this->db->sql_build_query('SELECT', $sqlArray);
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        return $row;
    }/*}}}*/

    public function updateWithTopicId($topicId, $data)/*{{{*/
    {
        $topicId = (int) $topicId;
        $sql = 'UPDATE ' . $this->tbl['req'] . '
            SET ' . $this->db->sql_build_array('UPDATE', $data) . "
            WHERE tid=${topicId}";
        $this->db->sql_query($sql);
    }/*}}}*/

}
