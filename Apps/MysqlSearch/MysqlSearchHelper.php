<?php
namespace jeb\snahp\Apps\MysqlSearch;

class MysqlSearchHelper
{
    const CACHE_DURATION = 0;
    const CACHE_DURATION_LONG = 0;
    const FLOODING_INTERVAL = 5;

    protected $db;/*{{{*/
    protected $config;
    protected $template;
    protected $tbl;
    protected $sauth;
    protected $forumHelper;
    public function __construct(
        $db,
        $config,
        $tbl,
        $groups,
        $sauth,
        $forumHelper
    ) {
        $this->db = $db;
        $this->config = $config;
        $this->tbl = $tbl;
        $this->groups = $groups;
        $this->sauth = $sauth;
        $this->forumHelper = $forumHelper;
        $this->userId = $sauth->userId;
        $this->rejectUnauthorizedAccess();
        $this->rejectFloodingRequests();
    }/*}}}*/

    public function userIsPermitted($userId)/*{{{*/
    {
        if ($this->sauth->is_dev_server()) {
            $groups = $this->groups['dev'];
        } else {
            $groups = $this->groups['production'];
        }
        return $this->sauth->user_belongs_to_groupset($userId, 'Staff')
            || $this->sauth->user_belongs_to_group($userId, $groups['Elite Users']['id']);
    }/*}}}*/

    public function rejectUnauthorizedAccess()/*{{{*/
    {
        if (!$this->userIsPermitted($this->userId)) {
            throw new UserPermissionError('You are not allowed to access this feature. Error Code: 84ceefc58a');
        }
    }/*}}}*/

    public function search($strn, $perPage=10, $start=0, $forumType='listings')/*{{{*/
    {
        $strn = $this->db->sql_escape($strn);

        $sqlAry = [
            'SELECT' => 'COUNT(*) as count',
            'FROM' => [TOPICS_TABLE => 't'],
            'WHERE' => "t.topic_title LIKE '%{$strn}%'",
            'ORDER_BY' => 't.topic_id DESC',
        ];
        $sqlAry = $this->filterForums($sqlAry, $forumType);
        $sql = $this->db->sql_build_query('SELECT', $sqlAry);
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        $total = $row['count'];

        $sqlAry['SELECT'] = 't.topic_title, t.topic_id';
        $sql = $this->db->sql_build_query('SELECT', $sqlAry);
        $result = $this->db->sql_query_limit($sql, $perPage, $start);
        $rowset = $this->db->sql_fetchrowset($result);
        $this->db->sql_freeresult($result);

        $this->updateUserLastSearchTime($this->userId);
        return [$rowset, $total];
    }/*}}}*/

    public function filterForums($sqlAry, $forumType)/*{{{*/
    {
        $allowedTypes = $this->forumTypeToAllowedTypes($forumType);
        $where[] = $sqlAry['WHERE'];
        $subForums = [];
        foreach ($allowedTypes as $type) {
            $rootForum = $this->getRootForumId($type);
            $subForums = array_merge($subForums, $this->forumHelper->selectSubforum($rootForum));
        }
        if (isset($subForums)) {
            $where[] = $this->db->sql_in_set('forum_id', $subForums);
        }
        $where = implode(' AND ', $where);
        $sqlAry['WHERE'] = $where;
        return $sqlAry;
    }/*}}}*/

    public function forumTypeToAllowedTypes($forumType)/*{{{*/
    {
        $allowedTypes = [];
        switch ($forumType) {
        case 'all':
            $allowedTypes = ['listings', 'requests'];
            break;
        case 'requests':
            $allowedTypes = ['requests'];
            break;
        case 'listings':
            $allowedTypes = ['listings'];
            break;
        }
        return $allowedTypes;
    }/*}}}*/


    public function getRootForumId($type)/*{{{*/
    {
        switch ($type) {
        case 'listings':
            return $this->config['snp_fid_listings'];
        case 'requests':
            return $this->config['snp_fid_requests'];
        default:
            throw new InvalidForumTypeError("$type is not supported. Error Code: c39f058c90");
        }
    }/*}}}*/

    public function rejectFloodingRequests()/*{{{*/
    {
        [$flooding, $left] = $this->userIsFlooding($this->userId);
        if ($flooding) {
            global $request;
            $uri = $request->server('REQUEST_URI');
            meta_refresh($left-0.5, $uri);
            throw new SearchFloodingError("You searching too quickly. Will automatically redirect in {$left} seconds. Error Code: 0c4b39d13a");
        }
    }/*}}}*/

    public function userIsFlooding($userId)/*{{{*/
    {
        $userId = (int) $userId;
        $sql = 'SELECT user_last_search FROM ' . USERS_TABLE
            . " WHERE user_id={$userId}";
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        if (!$row) {
            throw new InvalidUser('That user does not exist. Error Code: c7ce244486');
        }
        $elapsed = time() - $row['user_last_search'];
        $left = $this::FLOODING_INTERVAL - $elapsed;
        return [$left > 0, $left];
    }/*}}}*/

    public function updateUserLastSearchTime($userId)/*{{{*/
    {
        $userId = (int) $userId;
        $time = time();
        $sql = 'UPDATE ' . USERS_TABLE . " SET user_last_search=${time} WHERE user_id=${userId}";
        $this->db->sql_query($sql);
    }/*}}}*/
}

class UserPermissionError extends \Exception /*{{{*/
{
}/*}}}*/

class InvalidForumTypeError extends \Exception /*{{{*/
{
}/*}}}*/

class SearchFloodingError extends \Exception /*{{{*/
{
}/*}}}*/

class InvalidUser extends \Exception /*{{{*/
{
}/*}}}*/
