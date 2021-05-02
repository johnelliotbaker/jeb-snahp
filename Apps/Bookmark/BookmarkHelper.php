<?php
namespace jeb\snahp\Apps\Bookmark;

class BookmarkHelper
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
        $this->MAX_BOOKMARKS = 50;
    }

    public function removeUserBookmark($id)/*{{{*/
    {
        $bookmark = $this->getBookmark($id);
        $bookmarkOwner = $bookmark['user'];
        $perm = $this->sauth->is_dev() || $this->sauth->is_self($bookmarkOwner);
        if (!$perm) {
            throw new \Exception("Logging malicious activity. Error Code: 67bd4b04a7");
        }
        $sql = "DELETE FROM phpbb_snahp_bookmark WHERE id=${id}";
        $this->db->sql_query($sql);
    }/*}}}*/

    public function getBookmark($id)/*{{{*/
    {
        $id = (int) $id;
        $sqlArray = [
            'SELECT' => "a.*",
            'FROM'      => [ 'phpbb_snahp_bookmark' => 'a', ],
            'WHERE'     => "a.id={$id}",
        ];
        $queryset = $this->QuerySetFactory->fromSqlArray($sqlArray);
        $sql = $queryset->buildSql();
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        return $row;
    }/*}}}*/

    public function saveUserBookmark($userId, $type, $url, $params, $hash, $name)/*{{{*/
    {
        $count = $this->getUserBookmarksCount((int) $userId);
        if ($count >= $this->MAX_BOOKMARKS) {
            throw new \Exception("Too many bookmarks. Error Code: 96ee8b956d");
        }
        $params = json_decode($params, true);
        $data = [
            'user' => (int) $userId,
            'type' => $type,
            'topic' => 0,
        ];
        switch ($type) {
        case 'basic':
            $data['url'] = $url;
            $data['name'] = $name;
            break;
        case 'viewtopic':
            function getTopicId($params)
            {
                if ($topicId = (int) $params['t']) {
                    return $topicId;
                }
                return 0;
            }
            function getPostId($params, $hash)
            {
                if (preg_match("/^#p(\d+)$/", $hash, $match)) {
                    return $match[1];
                }
                if (array_key_exists('p', $params)) {
                    return $params['p'];
                }
            }
            function makeViewtopicURL($params, $hash)
            {
                $url = ['/viewtopic.php?'];
                foreach (['f', 't', 'p'] as $key) {
                    if ($params[$key]) {
                        $value = $params[$key];
                        $url[] = "$key=$value";
                    }
                }
                $url = implode('&', $url);
                if ($postId = getPostId($params, $hash)) {
                    $url .= '#p' . $postId;
                }
                return $url;
            }
            if ($topicId = getTopicId($params)) {
                $data['topic'] = $topicId;
            } elseif ($postId = getPostId($params, $hash)) {
                $sql = 'SELECT topic_id FROM ' . POSTS_TABLE . " WHERE post_id=$postId";
                $result = $this->db->sql_query($sql);
                $row = $this->db->sql_fetchrow($result);
                $this->db->sql_freeresult($result);
                if (!$row) {
                    throw new \Exception('No such topic');
                }
                $data['topic'] = $row['topic_id'];
            }
            $data['url'] = makeViewtopicURL($params, $hash);
            break;
        default:
            return;
        }
        $sql = 'INSERT INTO phpbb_snahp_bookmark ' . $this->db->sql_build_array('INSERT', $data);
        $this->db->sql_query($sql);
        return $data;
    }/*}}}*/

    public function getUserBookmarks($userId, $type)/*{{{*/
    {
        $userId = (int) $userId;
        switch ($type) {
        case 'viewtopic':
            return $this->getViewtopicUserBookmarks($userId);
        case 'basic':
            return $this->getBasicUserBookmarks($userId);
        default:
            throw new \Exception("Invalid type. Error Code: 091b85fe37");
        }
    }/*}}}*/

    public function getUserBookmarksCount($userId)/*{{{*/
    {
        $sqlArray = [
            'SELECT' => "Count(*) as total",
            'FROM'      => [ 'phpbb_snahp_bookmark' => 'a', ],
            'WHERE'     => "a.user={$userId}",
        ];
        $queryset = $this->QuerySetFactory->fromSqlArray($sqlArray);
        $sql = $queryset->buildSql();
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        return (int) ($row['total'] | 0);
    }/*}}}*/

    public function getViewtopicUserBookmarks($userId)/*{{{*/
    {
        $type = 'viewtopic';
        $sqlArray = [
            'SELECT' => "
                a.*, b.topic_title, b.topic_time, b.topic_last_post_time,
                b.topic_poster, b.topic_first_poster_name, b.topic_first_poster_colour,
                b.topic_last_poster_id, b.topic_last_poster_name, b.topic_last_poster_colour
                ",
            'FROM'      => [ 'phpbb_snahp_bookmark' => 'a', ],
            'LEFT_JOIN' => [
                [
                    'FROM' => [TOPICS_TABLE => 'b'],
                    'ON' => 'a.topic=b.topic_id'
                ]
            ],
            'WHERE'     => "a.user={$userId} AND a.type='{$type}'",
            'ORDER_BY'  => 'b.topic_time DESC',
        ];
        $queryset = $this->QuerySetFactory->fromSqlArray($sqlArray);
        $results = $this->paginator->paginateQueryset($queryset, $this->request);
        $results = $this->paginator->getPaginatedResult($results);
        foreach ($results['results'] as &$row) {
            $row['data'] = $data = unserialize($row['data']);
            $row['topic_first_poster_colour'] = '#' . $row['topic_first_poster_colour'];
            $row['topic_last_poster_colour'] = '#' . $row['topic_last_poster_colour'];
        }
        return $results;
    }/*}}}*/

    public function getBasicUserBookmarks($userId)/*{{{*/
    {
        $type = 'basic';
        $sqlArray = [
            'SELECT' => "a.*",
            'FROM'      => [ 'phpbb_snahp_bookmark' => 'a', ],
            'WHERE'     => "a.user={$userId} AND a.type='{$type}'",
            'ORDER_BY'  => 'a.id DESC',
        ];
        $queryset = $this->QuerySetFactory->fromSqlArray($sqlArray);
        $results = $this->paginator->paginateQueryset($queryset, $this->request);
        $results = $this->paginator->getPaginatedResult($results);
        return $results;
    }/*}}}*/
}
