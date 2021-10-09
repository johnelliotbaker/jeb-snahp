<?php
namespace jeb\snahp\Apps\Core\Db\query;

require_once "/var/www/forum/ext/jeb/snahp/Apps/Core/Db/query/BaseQuery.php";

use jeb\snahp\Apps\Core\Db\query\BaseQuery;

class Post extends BaseQuery
{
    public function __construct($db)
    {
        $this->db = $db;
        $this->OWN_TABLE_NAME = POSTS_TABLE;
        $this->ID_STRING = "post_id";
    }

    public function get($postId, $options = null)
    {
        $post = parent::get($postId);
        if (!$post) {
            $message = "Post (post_id = ${postId}) does not exist. Error Code: 2f829dcb5b";
            throwHttpException(404, $message);
        }
        return $post;
    }

    public function isPoster($postId, $userId)
    {
        $postId = (int) $postId;
        $userId = (int) $userId;
        $post = $this->get($postId);
        if ($post) {
            return (int) $post["poster_id"] === $userId;
        }
        return false;
    }

    public function isOP($postId, $userId)
    {
        $postId = (int) $postId;
        $userId = (int) $userId;
        $post = $this->get($postId);
        if (!$post) {
            return false;
        }
        $topicId = (int) $post["topic_id"];
        $tbl = TOPICS_TABLE;
        $sql = "SELECT topic_poster FROM $tbl WHERE topic_id=${topicId};";
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        return (int) $row["topic_poster"] === (int) $userId;
    }

    public function getForumId($postId)
    {
        $postId = (int) $postId;
        $post = $this->get($postId);
        if ($post) {
            return (int) $post["forum_id"];
        }
        return false;
    }
}
