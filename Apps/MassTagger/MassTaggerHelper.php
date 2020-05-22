<?php
namespace jeb\snahp\Apps\MassTagger;

class MassTaggerHelper
{
    protected $db;
    protected $user;
    protected $tbl;
    protected $sauth;
    protected $forumHelper;
    protected $tagsHelper;
    public function __construct(/*{{{*/
        $db,
        $user,
        $tbl,
        $sauth,
        $forumHelper,
        $tagsHelper
    ) {
        $this->db = $db;
        $this->user = $user;
        $this->tbl = $tbl;
        $this->sauth = $sauth;
        $this->forumHelper = $forumHelper;
        $this->tagsHelper = $tagsHelper;
        $this->user_id = $this->user->data['user_id'];
    }/*}}}*/

    public function getUserTopics($username, $rootForum=2, $start=0, $limit=10)/*{{{*/
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
    }/*}}}*/

    public function getUserTopicsCount($username)/*{{{*/
    {
        $topicPoster = (int) $this->sauth->userNameToUserId($username);
        $sql = 'SELECT count(*) as total FROM ' . TOPICS_TABLE .
            " WHERE topic_poster=${topicPoster}";
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        return $row ? $row['total'] : 0;
    }/*}}}*/

    public function topicsFromSearchTerms($searchTerms, $rootForum)/*{{{*/
    {
        $rootForum = (int) $rootForum;
        $searchTerms = $this->db->sql_escape((string) $searchTerms);
        $where[] = "a.topic_title LIKE '%{$searchTerms}%'";
        $subForums = $this->forumHelper->selectSubforum($rootForum);
        $where[] = $this->db->sql_in_set('forum_id', $subForums);
        $where = implode(' AND ', $where);
        $sql_ary = [
            'SELECT'   => 'a.topic_id',
            'FROM'     => [TOPICS_TABLE => 'a'],
            'WHERE'    => $where,
        ];
        $sql = $this->db->sql_build_query('SELECT', $sql_ary);
        $result = $this->db->sql_query($sql);
        $rowset = $this->db->sql_fetchrowset($result);
        $this->db->sql_freeresult($result);
        return $rowset;
    }/*}}}*/

    public function topicIdsFromTopics($topics)/*{{{*/
    {
        return array_map(
            function ($row) {
                return (int) $row['topic_id'];
            },
            $topics
        );
    }/*}}}*/

    public function topicIdsStringToList($strn)/*{{{*/
    {
        $list = explode(',', str_replace('#[,\s+]+#', ',', $strn));
        $list = array_map(
            function ($arg) {
                return (int) $arg;
            },
            $list
        );
        $list = array_filter(
            $list,
            function ($arg) {
                return $arg > 0;
            }
        );
        return $list;
    }/*}}}*/

    public function makeSubforumSelectorHTML($selectId)/*{{{*/
    {
        return $this->forumHelper->makeSubforumSelectorHTML($selectId);
    }/*}}}*/

    public function removeTags($topicId, $newTags)/*{{{*/
    {
        $existingTags = $this->tagsHelper->get_assigned_tags($topicId);
        $tags = array_diff($existingTags, $newTags);
        $allTags = $this->tagsHelper->split_valid_tags($tags);
        $validTags = $allTags['valid'];
        $this->tagsHelper->assign_tags_to_topic($topicId, $validTags);
    }/*}}}*/

    public function addTags($topicId, $newTags)/*{{{*/
    {
        $existingTags = $this->tagsHelper->get_assigned_tags($topicId);
        $tags = array_merge($existingTags, $newTags);
        $allTags = $this->tagsHelper->split_valid_tags($tags);
        $validTags = $allTags['valid'];
        $this->tagsHelper->assign_tags_to_topic($topicId, $validTags);
    }/*}}}*/

    public function getWhitelistTags()/*{{{*/
    {
        return $this->tagsHelper->get_whitelist_tags();
    }/*}}}*/

    public function getAllTags()/*{{{*/
    {
        $allTags = $this->tagsHelper->get_all_tags(0, 100);
        return array_map(
            function ($arg) {
                return $arg['tag'];
            },
            $allTags
        );
    }/*}}}*/
}
