<?php
namespace jeb\snahp\Apps\MuteUser;

class MuteUserHelper
{
    protected $db;
    protected $user;
    protected $tbl;
    protected $sauth;
    public function __construct(
        $db,
        $user,
        $tbl,
        $sauth
    ) {
        $this->db = $db;
        $this->user = $user;
        $this->tbl = $tbl;
        $this->sauth = $sauth;
        $this->user_id = $this->user->data['user_id'];
    }

    public function getMutedUserList($start=0, $limit=10)
    {
        $fields = [
            'user_id', 'username', 'user_colour',
            'snp_mute_topic', 'snp_mute_reply', 'snp_mute_created',
        ];
        $fields = implode(', ', $fields);
        $sql = "SELECT ${fields} FROM " . USERS_TABLE .
            " WHERE snp_mute_topic=1 OR snp_mute_reply=1";
        $result = $this->db->sql_query_limit($sql, $limit, $start);
        $rowset = $this->db->sql_fetchrowset($result);
        $this->db->sql_freeresult($result);
        return $rowset;
    }

    public function getMutedUserCount()
    {
        $sql = 'SELECT count(*) as total FROM ' . USERS_TABLE .
            " WHERE snp_mute_topic=1 OR snp_mute_reply=1";
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        return $row ? $row['total'] : 0;
    }

    private function _setNewTopicStatus($userId, $mute)
    {
        $status = $mute ? 1 : 0;
        $userId = (int) $userId;
        $created = time();
        $sql = 'UPDATE ' . USERS_TABLE .
            " SET snp_mute_topic=${status}, snp_mute_created=${created}" .
            " WHERE user_id=${userId}";
        $this->db->sql_query($sql);
    }

    private function _setPostReplyStatus($userId, $mute)
    {
        $status = $mute ? 1 : 0;
        $userId = (int) $userId;
        $created = time();
        $sql = 'UPDATE ' . USERS_TABLE .
            " SET snp_mute_reply=${status}, snp_mute_created=${created}" .
            " WHERE user_id=${userId}";
        $this->db->sql_query($sql);
    }

    public function muteUserNewTopic($user_id)
    {
        $this->_setNewTopicStatus($user_id, true);
    }

    public function unmuteUserNewTopic($user_id)
    {
        $this->_setNewTopicStatus($user_id, false);
    }

    public function muteUserPostReply($user_id)
    {
        $this->_setPostReplyStatus($user_id, true);
    }

    public function unmuteUserPostReply($user_id)
    {
        $this->_setPostReplyStatus($user_id, false);
    }
}
