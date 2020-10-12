<?php

namespace jeb\snahp\Apps\TopicUpdateTicker;

class EntryPermission
{
    public function __construct($db, $sauth)
    {
        $this->db = $db;
        $this->sauth = $sauth;
    }

    public function hasPermission($request, $userId, $kwargs=[])/*{{{*/
    {
        $data = getRequestData($request);
        $method = getRequestMethod($request);
        switch ($method) {
        case 'GET':
            return true;
        case 'POST':
            return $this->isAuthor($data['topic']);
        }
        return false;
    }/*}}}*/

    public function hasObjectPermission($request, $userId, $object, $kwargs=[])/*{{{*/
    {
        return $this->isAuthor($object->topic);
    }/*}}}*/

    public function isAuthor($topicId)/*{{{*/
    {
        $topicId = (int) $topicId;
        $sql = 'SELECT topic_poster FROM ' . TOPICS_TABLE . " WHERE topic_id=${topicId}";
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        if (!$row) {
            return false;
        }
        if ((int) $row['topic_poster'] === (int) $this->sauth->userId) {
            return true;
        }
        return false;
    }/*}}}*/
}
