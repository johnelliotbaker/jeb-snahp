<?php
namespace jeb\snahp\Apps\MassMover;

class MassMoverHelper
{
    protected $db;
    protected $user;
    protected $tbl;
    protected $sauth;
    protected $forumHelper;
    public function __construct(
        $db,
        $user,
        $tbl,
        $sauth,
        $forumHelper
    ) {
        $this->db = $db;
        $this->user = $user;
        $this->tbl = $tbl;
        $this->sauth = $sauth;
        $this->forumHelper = $forumHelper;
        $this->user_id = $this->user->data['user_id'];
    }

    public function getUserTopics($username, $rootForum=2, $start=0, $limit=10)
    {
        $topicPoster = (int) $this->sauth->userNameToUserId($username);
        $fields = [
            'topic_id', 'topic_title', 'topic_first_poster_name', // 'topic_id',
        ];
        $fields = implode(', ', $fields);
        $subForums = $this->forumHelper->selectSubforum($rootForum);
        $where[] = "topic_poster=${topicPoster}";
        $where[] = $this->db->sql_in_set('forum_id', $subForums);
        $where = implode(' AND ', $where);
        $sql_ary = [
            'SELECT'   => "$fields",
            'FROM'     => [TOPICS_TABLE => 'a'],
            'WHERE'    => $where,
        ];
        $sql    = $this->db->sql_build_query('SELECT', $sql_ary);
        $result = $this->db->sql_query_limit($sql, $limit, $start);
        $rowset = $this->db->sql_fetchrowset($result);
        $this->db->sql_freeresult($result);
        return $rowset;
    }

    public function getUserTopicsCount($username)
    {
        $topicPoster = (int) $this->sauth->userNameToUserId($username);
        $sql = 'SELECT count(*) as total FROM ' . TOPICS_TABLE .
            " WHERE topic_poster=${topicPoster}";
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        return $row ? $row['total'] : 0;
    }

    public function makeSubforumSelectorHTML($selectId)
    {
        return $this->forumHelper->makeSubforumSelectorHTML($selectId);
    }

    public function moveTopics($topics, $toForum)
    {
        include_once 'includes/functions_admin.php';
        if (is_array($topics) && count($topics)>0) {
            if (is_array($topics[0]) && array_key_exists('topic_id', $topics[0])) {
                $topics = array_map(
                    function ($topic) {
                        return $topic['topic_id'];
                    },
                    $topics
                );
            }
            move_topics($topics, (int) $toForum, $auto_sync = true);
        }
        return true;
    }
}
