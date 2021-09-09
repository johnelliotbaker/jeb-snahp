<?php

namespace jeb\snahp\Apps\DeadLinks;

require_once "/var/www/forum/ext/jeb/snahp/core/Rest/Views/Generics.php";
require_once "/var/www/forum/ext/jeb/snahp/core/Rest/Permissions/Permission.php";

use Symfony\Component\HttpFoundation\JsonResponse;
use \R as R;

class TopicView
{
    protected $db;
    protected $request;
    protected $sauth;
    protected $Entry;
    public function __construct($db, $request, $sauth, $Entry)
    {
        $this->db = $db;
        $this->request = $request;
        $this->sauth = $sauth;
        $this->Entry = $Entry;
    }

    public function view($topicId)
    {
        $data = $this->getTopicData($topicId);
        return new JsonResponse($data);
    }

    public function getTopicData($topicId)
    {
        $topicId = (int) $topicId;
        $sql =
            "SELECT topic_id, topic_title, topic_poster, topic_first_poster_name, topic_first_poster_colour FROM " .
            TOPICS_TABLE .
            " WHERE topic_id=${topicId}";
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        if (!$row) {
            return $row;
        }
        $entry = $this->Entry->activeEntry($topicId);
        $row["last_entry"] = ["type" => $entry["type"]];
        $row["topic_poster"] = (int) $row["topic_poster"];
        $row["current_user"] = (int) $this->sauth->userId;
        $row["is_author"] = $row["topic_poster"] === $row["current_user"];
        if ($this->sauth->is_dev()) {
            $row["is_mod"] = true;
        }
        return $row;
    }
}
