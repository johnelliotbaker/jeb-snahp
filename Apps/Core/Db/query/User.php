<?php
namespace jeb\snahp\Apps\Core\Db\query;

class User
{
    public function __construct($db)
    {
        $this->db = $db;
    }

    public function get($userId, $options)/*{{{*/
    {
        $fields = $options['fields'] ?? '*';
        $userId = (int) $userId;
        $sqlArray = [
            'SELECT' => $fields,
            'FROM' => [USERS_TABLE => 'a'],
            'WHERE' => "user_id=${userId}",
        ];
        $sql = $this->db->sql_build_query('SELECT', $sqlArray);
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        return $row;
    }/*}}}*/
}
