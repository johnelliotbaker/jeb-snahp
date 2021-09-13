<?php
namespace jeb\snahp\Apps\Core\Db\query;

require_once "/var/www/forum/ext/jeb/snahp/Apps/Core/Db/query/BaseQuery.php";

use jeb\snahp\Apps\Core\Db\query\BaseQuery;
use phpbb\exception\http_exception;

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
}
