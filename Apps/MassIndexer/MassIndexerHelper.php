<?php
namespace jeb\snahp\Apps\MassIndexer;

class MassIndexerHelper
{
    const CACHE_DURATION = 0;
    const CACHE_DURATION_LONG = 0;
    const CHUNK_SIZE = 50;

    protected $db;
    protected $user;
    protected $template;
    protected $tbl;
    protected $sauth;
    protected $forumHelper;
    public function __construct(
        $db,
        $user,
        $template,
        $tbl,
        $sauth,
        $forumHelper
    ) {
        $this->db = $db;
        $this->user = $user;
        $this->template = $template;
        $this->tbl = $tbl;
        $this->rxnTbl = $tbl['reaction'];
        $this->sauth = $sauth;
        $this->forumHelper = $forumHelper;
        $this->user_id = $this->user->data['user_id'];
    }

    public function unindexAllPostsByUsername($username, $rootForum=null)
    {
        $userId = $this->sauth->userNameToUserId($username);
        $this->rejectInvalidUser($userId, $username);
        $this->rejectInvalidForum($rootForum);
        $search = $this->getSearchInstance();
        $rowset = $this->getPostsRelatedToUser($userId, $rootForum);
        $postIds = array_map(
            function ($row) {
                return $row['post_id'];
            },
            $rowset
        );
        $total = count($postIds);
        $chunks = array_chunk($postIds, $this::CHUNK_SIZE);
        foreach ($chunks as $chunk) {
            $search->index_remove($chunk, [], []);
        }
        return $total;
    }

    private function getPostsRelatedToUser($userId, $rootForum)
    {
        $where[] = "a.topic_poster={$userId}";
        if ($rootForum) {
            $subForums = $this->forumHelper->selectSubforum(
                $rootForum
            );
            if ($subForums) {
                $where[] = $this->db->sql_in_set('b.forum_id', $subForums);
            }
        }
        $where = implode(' AND ', $where);
        $sql_ary = [
            'SELECT'   => 'b.post_id',
            'FROM'     => [TOPICS_TABLE => 'a'],
            'LEFT_JOIN' => [
                [
                    'FROM'  => [POSTS_TABLE => 'b'],
                    'ON'    => 'a.topic_id=b.topic_id',
                ],
            ],
            'WHERE'    => $where,
        ];
        $sql    = $this->db->sql_build_query('SELECT', $sql_ary);
        $result = $this->db->sql_query($sql);
        $rowset = $this->db->sql_fetchrowset($result);
        $this->db->sql_freeresult($result);
        return $rowset;
    }

    private function getSearchInstance()
    {
        global $phpbb_root_path, $phpEx, $auth, $config;
        global $db, $user, $phpbb_dispatcher;
        $search_type = $config['search_type'];
        if (!class_exists($search_type)) {
            trigger_error('NO_SUCH_SEARCH_MODULE');
        }
        $error = false;
        return new $search_type(
            $error,
            $phpbb_root_path,
            $phpEx,
            $auth,
            $config,
            $db,
            $user,
            $phpbb_dispatcher
        );
    }

    private function rejectInvalidForum($forumId)
    {
        if (!$forumId) {
            trigger_error(
                "Forum '{$forumId}' does not exist. " .
                'Error Code: 96f0765076'
            );
        }
    }

    private function rejectInvalidUser($userId, $username='')
    {
        if (!$userId) {
            trigger_error(
                "Username '{$username}' does not exist. " .
                'Error Code: 4781f662d8'
            );
        }
    }

    public function unindexAllPostsByTopic($topicId)
    {
        $search = $this->getSearchInstance();
        $rowset = $this->getPostsRelatedToTopic($topicId);
        $postIds = array_map(
            function ($row) {
                return $row['post_id'];
            },
            $rowset
        );
        $total = count($postIds);
        $chunks = array_chunk($postIds, $this::CHUNK_SIZE);
        foreach ($chunks as $chunk) {
            $search->index_remove($chunk, [], []);
        }
        return $total;
    }

    private function getPostsRelatedToTopic($topicId)
    {
        $where[] = "a.topic_id={$topicId}";
        $sql_ary = [
            'SELECT'   => 'b.post_id',
            'FROM'     => [TOPICS_TABLE => 'a'],
            'LEFT_JOIN' => [
                [
                    'FROM'  => [POSTS_TABLE => 'b'],
                    'ON'    => 'a.topic_id=b.topic_id',
                ],
            ],
            'WHERE'    => $where,
        ];
        $sql    = $this->db->sql_build_query('SELECT', $sql_ary);
        $result = $this->db->sql_query($sql);
        $rowset = $this->db->sql_fetchrowset($result);
        $this->db->sql_freeresult($result);
        return $rowset;
    }

    public function unindexAllPostsByForum($forumId)
    {
        $search = $this->getSearchInstance();
        $rowset = $this->getPostsRelatedToForum($forumId);
        $postIds = array_map(
            function ($row) {
                return $row['post_id'];
            },
            $rowset
        );
        $total = count($postIds);
        $chunks = array_chunk($postIds, $this::CHUNK_SIZE);
        foreach ($chunks as $chunk) {
            $search->index_remove($chunk, [], []);
        }
        return $total;
    }

    private function getPostsRelatedToForum($forumId)
    {
        $where[] = "a.forum_id={$forumId}";
        $sql_ary = [
            'SELECT'   => 'b.post_id',
            'FROM'     => [FORUMS_TABLE => 'a'],
            'LEFT_JOIN' => [
                [
                    'FROM'  => [POSTS_TABLE => 'b'],
                    'ON'    => 'a.forum_id=b.forum_id',
                ],
            ],
            'WHERE'    => $where,
        ];
        $sql    = $this->db->sql_build_query('SELECT', $sql_ary);
        $result = $this->db->sql_query($sql);
        $rowset = $this->db->sql_fetchrowset($result);
        $this->db->sql_freeresult($result);
        return $rowset;
    }
}
