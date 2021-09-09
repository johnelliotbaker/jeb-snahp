<?php

namespace jeb\snahp\Apps\DeadLinks\Models;

// require_once '/var/www/forum/ext/jeb/snahp/core/Rest/Model.php';
require_once "/var/www/forum/ext/jeb/snahp/core/Rest/Model.php";

use jeb\snahp\core\Rest\Model;
use jeb\snahp\core\Rest\Fields\IntegerField;
use jeb\snahp\core\Rest\Fields\StringField;

use \R as R;

class Entry extends Model
{
    const TABLE_NAME = "phpbb_snahp_deadlinks_entry";

    private $cache;
    protected $db;
    protected $Notifier;
    protected $fields;
    protected $requiredFields = [
        "topic",
        "type",
        "subtype",
        "status",
        "user",
        "comment",
        "created",
        "modified",
    ];

    public function __construct($db, $Notifier, $sauth)
    {
        parent::__construct();
        $time = time();
        $this->cache = [];
        $this->db = $db;
        $this->Notifier = $Notifier;
        $this->fields = [
            "topic" => new IntegerField(),
            "type" => new StringField(),
            "subtype" => new StringField(),
            "comment" => new StringField(),
            "status" => new StringField(["default" => "Open"]),
            "user" => new IntegerField(["default" => (int) $sauth->userId]),
            "created" => new IntegerField(["default" => $time]),
            "modified" => new IntegerField(["default" => $time]),
        ];
    }

    public function manage($object)
    {
        // Main manager for reporting work flow
        // Triggered from performPostCreate
        // Should: send notification first THEN close
        // Because: notification uses lastOpenReport data
        switch ($object->type) {
            case "Report":
                if ($object->subtype === "Deadlink") {
                    $this->markTopicWithDeadlink($object->topic);
                }
                break;
            case "Request":
                switch ($object->subtype) {
                    case "ReUp":
                        $this->sendReUpNotification($object);
                        $this->closeTopicRequests($object->topic);
                        $this->closeTopicReports($object->topic);
                        $this->unmarkTopicWithDeadlink($object->topic);
                        break;
                    case "Refute":
                        $this->sendRefuteNotification($object);
                        $this->closeTopicRequests($object->topic);
                        $this->closeTopicReports($object->topic);
                        $this->unmarkTopicWithDeadlink($object->topic);
                        break;
                    case "Removed":
                        $this->sendRemovedNotification($object);
                        $this->closeTopicRequests($object->topic);
                        $this->closeTopicReports($object->topic);
                        $this->unmarkTopicWithDeadlink($object->topic);
                        break;
                    case "Graveyard":
                        $this->sendGraveyardNotification($object);
                        $this->graveyardTopic($object->topic);
                        $this->closeTopic($object->topic);
                        break;
                }
                break;
            case "Action":
                switch ($object->subtype) {
                    case "Graveyard":
                        $this->sendGraveyardNotification($object);
                        $this->graveyardTopic($object->topic);
                        break;
                    case "Close":
                        $this->sendCloseNotification($object);
                        break;
                    default:
                        break;
                }
                $this->closeTopic($object->topic);
        }
    }

    public function graveyardTopic($topicId)
    {
        global $config;
        $graveyard_fid = (int) unserialize($config["snp_cron_graveyard_fid"])[
            "default"
        ];
        $topics = [$topicId];
        include_once "includes/functions_admin.php";
        move_topics($topics, $graveyard_fid, $auto_sync = true);
        $this->unIndexTopic($topicId);
    }

    public function performPostCreate($object)
    {
        return $this->manage($object);
    }

    public function markTopicWithDeadlink($topicId)
    {
        $sql =
            "UPDATE " .
            TOPICS_TABLE .
            " SET snp_ded_b_dead=1 WHERE topic_id=${topicId}";
        $this->db->sql_query($sql);
    }

    public function unmarkTopicWithDeadlink($topicId)
    {
        $sql =
            "UPDATE " .
            TOPICS_TABLE .
            " SET snp_ded_b_dead=0 WHERE topic_id=${topicId}";
        $this->db->sql_query($sql);
    }

    public function closeTopicRequests($topicId)
    {
        $entries = R::find($this::TABLE_NAME, 'topic=? AND type="Request"', [
            $topicId,
        ]);
        foreach ($entries as $entry) {
            $entry->status = "Closed";
            R::store($entry);
        }
    }

    public function closeTopicReports($topicId)
    {
        $entries = R::find($this::TABLE_NAME, 'topic=? AND type="Report"', [
            $topicId,
        ]);
        foreach ($entries as $entry) {
            $entry->status = "Closed";
            R::store($entry);
        }
    }

    public function closeTopic($topicId)
    {
        $entries = R::find($this::TABLE_NAME, "topic=?", [$topicId]);
        foreach ($entries as $entry) {
            $entry->status = "Closed";
            R::store($entry);
        }
    }

    public function lastOpenRequest($topicId)
    {
        return $this->getObject(
            'topic=? AND type="Request" AND status=? ORDER BY id DESC',
            [$topicId, "Open"]
        );
    }

    public function lastOpenAction($topicId)
    {
        return $this->getObject(
            'topic=? AND type="Action" AND status=? ORDER BY id DESC',
            [$topicId, "Open"]
        );
    }

    public function lastOpenReport($topicId)
    {
        return $this->getObject(
            'topic=? AND type="Report" AND status=? ORDER BY id DESC',
            [$topicId, "Open"]
        );
    }

    public function activeEntry($topicId)
    {
        if ($action = $this->lastOpenAction($topicId)) {
            return $action;
        }
        if ($request = $this->lastOpenRequest($topicId)) {
            return $request;
        }
        if ($report = $this->lastOpenReport($topicId)) {
            return $report;
        }
    }

    public function getTopicData($topicId)
    {
        $topicId = (int) $topicId;
        $sql =
            "SELECT topic_poster, topic_title FROM " .
            TOPICS_TABLE .
            " WHERE topic_id=${topicId}";
        $result = $this->db->sql_query($sql, 1);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        return $row;
    }

    public function getOpenRequests()
    {
        [$maxi_query, $cooldown] = [200, 0];
        $sql_array = [
            "SELECT" => "b.topic_id, b.topic_title, a.subtype",
            "FROM" => ["phpbb_snahp_deadlinks_entry" => "a"],
            "LEFT_JOIN" => [
                [
                    "FROM" => [TOPICS_TABLE => "b"],
                    "ON" => "a.topic=b.topic_id",
                ],
            ],
            "WHERE" => "a.status='Open' AND a.type='Request'",
            "ORDER_BY" => "id DESC",
        ];
        $sql = $this->db->sql_build_query("SELECT", $sql_array);
        $result = $this->db->sql_query_limit($sql, $maxi_query, 0, $cooldown);
        $rowset = $this->db->sql_fetchrowset($result);
        $this->db->sql_freeresult($result);
        return $rowset;
    }

    public function getOpenReports($userId)
    {
        $userId = (int) $userId;
        [$maxi_query, $cooldown] = [200, 0];
        $sql_array = [
            "SELECT" => "b.topic_id, b.topic_title",
            "FROM" => ["phpbb_snahp_deadlinks_entry" => "a"],
            "LEFT_JOIN" => [
                [
                    "FROM" => [TOPICS_TABLE => "b"],
                    "ON" => "a.topic=b.topic_id",
                ],
            ],
            "WHERE" => "b.topic_poster=${userId} AND a.status='Open' AND a.type='Report'",
            "ORDER_BY" => "id DESC",
        ];
        $sql = $this->db->sql_build_query("SELECT", $sql_array);
        $result = $this->db->sql_query_limit($sql, $maxi_query, 0, $cooldown);
        $rowset = $this->db->sql_fetchrowset($result);
        $this->db->sql_freeresult($result);
        return $rowset;
    }

    public function sendRemovedNotification($object)
    {
        $this->sendNotificationToReporter(
            $object->topic,
            "The reported link was removed"
        );
    }

    public function sendRefuteNotification($object)
    {
        $this->sendNotificationToReporter(
            $object->topic,
            "No deadlink was found on this thread"
        );
    }

    public function sendReUpNotification($object)
    {
        $this->sendNotificationToReporter($object->topic, "Link restored");
    }

    public function sendGraveyardNotification($object)
    {
        $this->sendNotificationToReporter(
            $object->topic,
            "The reported topic was moved to the graveyard"
        );
    }

    public function sendCloseNotification($object)
    {
        $this->sendNotificationToReporter(
            $object->topic,
            "Moderator closed your deadlink report without further action"
        );
    }

    public function sendNotificationToReporter($topicId, $title)
    {
        $topicData = $this->getTopicData($topicId);
        if (!$topicData) {
            return;
        }
        $lastReport = $this->lastOpenReport($topicId);
        $reporter = (int) $lastReport->user;
        $data = [
            "userId" => $reporter,
            "topicId" => $topicId,
            "time" => time(),
            "title" => $title,
            "message" => $topicData["topic_title"],
        ];
        $this->clearNotifications($topicId);
        $this->Notifier->add_notifications(
            ["jeb.snahp.notification.type.deadlinks"],
            $data
        );
    }

    public function clearNotifications($topicId)
    {
        $notificationTypeId = (int) $this->Notifier->get_notification_type_id(
            "jeb.snahp.notification.type.deadlinks"
        );
        $time = time();
        $sql =
            "DELETE FROM " .
            NOTIFICATIONS_TABLE .
            "
            WHERE item_id={$topicId} AND notification_type_id={$notificationTypeId}";
        $this->db->sql_query($sql);
    }

    public function unIndexTopic($topicId)
    {
        $CHUNK_SIZE = 50;
        $search = $this->getSearchInstance();
        $sql =
            "SELECT post_id FROM " . POSTS_TABLE . " WHERE topic_id=${topicId}";
        $result = $this->db->sql_query($sql);
        $rowset = $this->db->sql_fetchrowset($result);
        $this->db->sql_freeresult($result);
        $postIds = array_map(function ($row) {
            return $row["post_id"];
        }, $rowset);
        $chunks = array_chunk($postIds, $CHUNK_SIZE);
        foreach ($chunks as $chunk) {
            $search->index_remove($chunk, [], []);
        }
    }

    private function getSearchInstance()
    {
        global $phpbb_root_path, $phpEx, $auth, $config;
        global $db, $user, $phpbb_dispatcher;
        $search_type = $config["search_type"];
        if (!class_exists($search_type)) {
            trigger_error("NO_SUCH_SEARCH_MODULE");
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

    public function isAuthor($topicId, $userId)
    {
        $topicData = $this->getTopicData($topicId);
        return (int) $topicData["topic_poster"] === (int) $userId;
    }
}
