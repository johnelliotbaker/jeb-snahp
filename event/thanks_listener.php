<?php

namespace jeb\snahp\event;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class thanks_listener implements EventSubscriberInterface
{
    protected $db;
    protected $user;
    protected $config;
    protected $container;
    protected $tbl;
    protected $sauth;
    protected $data;
    protected $thanksUsers;
    public function __construct(
        $db,
        $user,
        $config,
        $container,
        $tbl,
        $sauth,
        $thanksUsers
    ) {
        $this->db = $db;
        $this->user = $user;
        $this->config = $config;
        $this->container = $container;
        $this->tbl = $tbl;
        $this->sauth = $sauth;
        $this->thanksUsers = $thanksUsers;
        $this->n_allowed_per_cycle = 2;
        $this->cycle_in_seconds = (int) $config["snp_thanks_cycle_duration"];
        $this->data = [];
        $this->sql_cooldown = 0;
    }

    public static function getSubscribedEvents()
    {
        return [
            "gfksx.thanksforposts.output_thanks_before" =>
                "modify_avatar_thanks",
            "gfksx.thanksforposts.insert_thanks_before" => "insertThanks",
        ];
    }

    public function insertThanks($event)
    {
        if (!$this->config["snp_thanks_b_enable"]) {
            return false;
        }
        $this->sauth->rejectRestrictedUser();
        $fromId = $event["from_id"];
        $toId = $event["to_id"];
        $topicId = $event["topic_id"];
        if ($this->config["snp_thanks_b_limit_cycle"]) {
            $thanksUserData = $this->thanksUsers->setup($fromId);
            $this->thanksUsers->rejectBannedUser($fromId);
            $this->thanksUsers->rejectExcessiveThanksPerCycle($thanksUserData);
        }
        $this->thanksUsers->addThanksGivenStatistics($fromId);
        $this->thanksUsers->addThanksReceivedStatistics($toId);
        $this->thanksUsers->insertTimestamp($fromId, $topicId);
    }

    public function modify_avatar_thanks($event)
    {
        $poster_id = $event["poster_id"];
        $sql =
            "SELECT snp_disable_avatar_thanks_link FROM " .
            USERS_TABLE .
            " WHERE user_id=" .
            $poster_id;
        $result = $this->db->sql_query($sql, 30);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        $b_disable_avatar_thanks_link = false;
        if ($row) {
            $b_disable_avatar_thanks_link =
                $row["snp_disable_avatar_thanks_link"];
        }
        if ($b_disable_avatar_thanks_link) {
            $event["u_receive_count_url"] = false;
            $event["u_give_count_url"] = false;
        }
    }
}
