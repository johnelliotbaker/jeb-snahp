<?php
namespace jeb\snahp\Apps\UserBlock;

class UserBlockHelper
{
    public function __construct(
        $db,
        $request,
        $tbl,
        $sauth,
        $pageNumberPagination,
        $QuerySetFactory
    ) {
        $this->db = $db;
        $this->request = $request;
        $this->tbl = $tbl;
        $this->sauth = $sauth;
        $this->paginator = $pageNumberPagination;
        $this->QuerySetFactory = $QuerySetFactory;
        $this->userId = $sauth->userId;
    }

    public function getUserGroupData($groupId)
    {
        $groupId = (int) $groupId;
        $sql = 'SELECT * FROM ' . GROUPS_TABLE . " WHERE group_id=${groupId}";
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        return $row;
    }

    public function removeAllBlocksFromUserGroup($groupId)
    {
        $sql = 'SELECT * FROM ' . $this->tbl['foe'];
        $result = $this->db->sql_query($sql);
        $rowset = $this->db->sql_fetchrowset($result);
        $this->db->sql_freeresult($result);
        $users = [];
        $res = [];
        foreach ($rowset as $k => $row) {
            $blockerId = $row['blocker_id'];
            if (!array_key_exists($blockerId, $users)) {
                if ($this->sauth->user_belongs_to_group($blockerId, $groupId)) {
                    $users[$blockerId] = true;
                    $res[] = $this->sauth->userId2ProfileLink($blockerId);
                }
            }
        }
        unset($blockerId);
        $users = array_keys($users);
        foreach ($users as $blockerId) {
            $sql = 'DELETE FROM ' . $this->tbl['foe'] . " WHERE blocker_id=${blockerId}";
            $this->db->sql_query($sql);
        }
        return $res;
    }

    public function getUserBlocksLog($username)
    {
        $whereArray = ['type="LOG_FOE_BLOCKER"'];
        if ($targetUserId = $this->sauth->userNameToUserId($username)) {
            $whereArray[] = "b.user_id=$targetUserId OR c.user_id=$targetUserId";
        }
        $sqlArray = [
            'SELECT' => 'a.*,
            b.user_id as blocker_user_id, b.username as blocker_username, b.user_colour as blocker_user_colour,
            c.user_id as blocked_user_id, c.username as blocked_username, c.user_colour as blocked_user_colour',
        'FROM' => [$this->tbl['log']=> 'a'],
            'LEFT_JOIN' => [
                [
                    'FROM' => [USERS_TABLE => 'b'],
                    'ON' => 'a.user_id=b.user_id',
                ],
                [
                    'FROM' => [USERS_TABLE => 'c'],
                    'ON' => 'a.target_id=c.user_id',
                ],
            ],
            'WHERE' => implode(' AND ', $whereArray),
            'ORDER_BY' => 'id DESC',
        ];
        $queryset = $this->QuerySetFactory->fromSqlArray($sqlArray);
        $results = $this->paginator->paginateQueryset($queryset, $this->request);
        $results = $this->paginator->getPaginatedResult($results);
        foreach ($results['results'] as &$row) {
            $row['data'] = $data = unserialize($row['data']);
            $row['blocker_user_colour'] = '#' . $row['blocker_user_colour'];
            $row['blocked_user_colour'] = '#' . $row['blocked_user_colour'];
        }
        return $results;
    }

    public function getUserBlocks($username)
    {
        $sqlArray = [
            'SELECT'    => 'a.*, b.username as blocker_username, c.username as blocked_username',
            'FROM'      => [$this->tbl['foe'] => 'a'],
            'LEFT_JOIN' => [
                [
                    'FROM' => [USERS_TABLE => 'b'],
                    'ON' => 'a.blocker_id=b.user_id'
                ],
                [
                    'FROM' => [USERS_TABLE => 'c'],
                    'ON' => 'a.blocked_id=c.user_id'
                ],
            ],
            'ORDER_BY' => 'id DESC',
        ];
        if ($username) {
            $sqlArray['WHERE'] = "b.username='$username' OR c.username='$username'";
        }
        $queryset = $this->QuerySetFactory->fromSqlArray($sqlArray);
        $results = $this->paginator->paginateQueryset($queryset, $this->request);
        $results = $this->paginator->getPaginatedResult($results);
        return $results;
    }
}
