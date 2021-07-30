<?php
namespace jeb\snahp\Apps\TopicBump;

const DEFAULT_BUMP_USER_DATA = [
    'banned' => 0,
    'ban_start' => 0,
    'ban_end' => 0,
    'ban_reason' => '',
    'ban_by' => 0,
];

class TopicBumpHelper
{
    public function __construct(
        $db,
        $tbl,
        $sauth,
        $pageNumberPagination,
        $QuerySetFactory,
        $topicBumpParams
    ) {
        $this->db = $db;
        $this->tbl = $tbl;
        $this->sauth = $sauth;
        $this->paginator = $pageNumberPagination;
        $this->QuerySetFactory = $QuerySetFactory;
        $this->definition = $topicBumpParams['def'];
        $this->userId = $sauth->userId;
    }/*}}}*/

    // Moderation

    public function getBumpUser($userId)/*{{{*/
    {
        $sqlArray = [
            'SELECT'   => '*',
            'FROM'     => [$this->tbl['bump_user'] => 'a'],
            'WHERE'    => "user={$userId}",
        ];
        $qs = $this->QuerySetFactory->fromSqlArray($sqlArray);
        return $qs->offsetGet(0);
    }/*}}}*/

    public function createBumpUser($userId)/*{{{*/
    {
        if ($this->getBumpUser($userId)) {
            return;
        }
        $t = $this->tbl['bump_user'];
        $data = [ 'user' => $userId, 'ban_reason' => '' ];
        $sql = "INSERT INTO $t" . $this->db->sql_build_array('INSERT', $data);
        $this->db->sql_query($sql);
    }/*}}}*/

    public function removeBumpUser($userId)/*{{{*/
    {
        $t = $this->tbl['bump_user'];
        $sql = "DELETE FROM $t WHERE user={$userId}";
        $this->db->sql_query($sql);
    }/*}}}*/

    public function updateBumpUserData($userId, $data)/*{{{*/
    {
        $userId = (int) $userId;
        if (!$this->getBumpUser($userId)) {
            return;
        }
        $t = $this->tbl['bump_user'];
        $sql = "UPDATE $t SET "
            . $this->db->sql_build_array('UPDATE', $data)
            . " WHERE user=$userId";
        $this->db->sql_query($sql);
    }/*}}}*/

    public function banUserByUsername($username, $duration, $reason='')/*{{{*/
    {
        $userId = $this->sauth->userNameToUserId($username);
        if (!$userId) {
            throw new \Exception('User Not Found. Error Code: aa316eed68');
        }
        $this->createBumpUser($userId);
        $now = time();
        $duration = (int) max($duration, 0);
        if ($duration === 0) {
            return $this->unBanUser($userId);
        }
        $data = [
            'banned' => 1,
            'ban_start' => $now,
            'ban_end' => $now + $duration,
            'ban_reason' => $reason,
            'ban_by' => $this->userId,
        ];
        $this->updateBumpUserData($userId, $data);
    }/*}}}*/

    public function banUser($userId, $duration, $reason='')/*{{{*/
    {
        $this->createBumpUser($userId);
        $now = time();
        $duration = max($duration, 0);
        $data = [
            'banned' => 1,
            'ban_start' => $now,
            'ban_end' => $now + $duration,
            'ban_reason' => $reason,
            'ban_by' => $this->userId,
        ];
        $this->updateBumpUserData($userId, $data);
    }/*}}}*/

    public function unBanUser($userId)/*{{{*/
    {
        if (!$this->getBumpUser($userId)) {
            return;
        }
        $this->removeBumpUser($userId);
    }/*}}}*/

    public function userIsBannedOrRemoveBumpUser($userId)/*{{{*/
    {
        $effectivelyBanned = $this->userIsEffectivelyBanned($userId);
        if (!$effectivelyBanned) {
            $this->removeBumpUser($userId);
        }
        return $effectivelyBanned;
    }/*}}}*/

    public function userIsEffectivelyBanned($userId)/*{{{*/
    {
        $bumpUser = $this->getBumpUser($userId);
        if (!$bumpUser['banned'] || $this->userBanExpired($bumpUser)) {
            return false;
        }
        return true;
    }/*}}}*/

    public function userBanExpired($bumpUser)/*{{{*/
    {
        return $bumpUser['ban_end'] < time();
    }/*}}}*/

    public function getBumpUserToplist($request)/*{{{*/
    {
        $t = $this->tbl['bump_user'];
        $username = utf8_clean_string($request->variable('username', ''));
        $orderBy = $request->variable('order-by', 'id');
        $whereArray = [];
        if ($username) {
            $whereArray[] = "b.username_clean LIKE '${username}%'";
        }
        $where = implode(' AND ', $whereArray);
        $sqlArray = [
            'SELECT' => 'b.username, b.user_colour, b.user_id, a.*',
            'FROM' => [$t => 'a'],
            'LEFT_JOIN' => [
                [
                    'FROM' => [ USERS_TABLE => 'b', ],
                    'ON' => 'a.user=b.user_id',
                ],
            ],
            'WHERE' => $where,
            'ORDER_BY' => "a.{$orderBy} DESC"
        ];
        $queryset = $this->QuerySetFactory->fromSqlArray($sqlArray);
        $results = $this->paginator->paginateQueryset($queryset, $request);
        return $this->paginator->getPaginatedResult($results);
    }/*}}}*/

    public function resetBumpUser($userId)/*{{{*/
    {
        $data = DEFAULT_BUMP_USER_DATA;
        $this->updateBumpUserData($userId, $data);
    }/*}}}*/

    public function editBumpUser($userId, $requestData)/*{{{*/
    {
        if ($this->getBumpUser($userId)) {
            return;
        }
        $ALLOWED_FIELDS = ['reason', 'ban_start', 'ban_end'];
        $data = [];
        foreach ($requestData as $key => $value) {
            if (in_array($key, $ALLOWED_FIELDS)) {
                $data[$key] = $value;
            }
        }
        $this->updateBumpUserData($userId, $data);
    }/*}}}*/

    // Client

    public function getBumpContext($topicId)/*{{{*/
    {
        include_once '/var/www/forum/ext/jeb/snahp/core/functions_phpbb.php';
        $def = $this->definition;
        $topicData = getTopicData($topicId);
        $bumpData = $this->getTopicBumpData($topicId);
        $bGroupEnabled = $this->sauth->getMaxFromGroupMemberships($this->userId, 'snp_enable_bump');

        $bMod = $this->sauth->is_dev();
        $status = $bumpData ? $bumpData['status'] : null;
        $bOp = $topicData && $topicData['topic_poster'] == $this->userId;
        // Conditions
        $bDisable = ($bMod && $status == $def['enable']);
        $bEnable = $bMod && (!$bumpData || $status == $def['disable']);
        $bEnable |= ($bOp && $bGroupEnabled && !$bumpData);
        $bBump = ($bMod || $bOp) && ($bumpData && $status == $def['enable']);
        $permissions = [
            'enable' => (bool) $bEnable,
            'disable' => (bool) $bDisable,
            'bump' => (bool) $bBump,
        ];
        $data = [
            'permissions' => $permissions,
            'bumpData' => $bumpData,
            'topicData' => $topicData,
            'def' => $def,
        ];
        return $data;
    }/*}}}*/

    public function getTopicBumpData($topicId)/*{{{*/
    {
        $topicId = (int) $topicId;
        $t = $this->tbl['bump_topic'];
        $sql = "SELECT * FROM $t WHERE tid=$topicId";
        $result = $this->db->sql_query_limit($sql, 1);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        return $row;
    }/*}}}*/

    public function getBumpCooldown($userId)/*{{{*/
    {
        return (int) $this->sauth->getMinFromGroupMemberships($this->userId, 'snp_bump_cooldown');
    }/*}}}*/
}
