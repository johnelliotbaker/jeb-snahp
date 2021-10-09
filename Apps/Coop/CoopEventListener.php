<?php
namespace jeb\snahp\Apps\Coop;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use phpbb\config\config;
use jeb\snahp\Apps\Coop\CoopHelper;
use jeb\snahp\core\auth\user_auth;

define("TABLE_COOP_FORUM", "phpbb_snahp_coop_forum");
define("TABLE_COOP_TOPIC", "phpbb_snahp_coop_topic");
define("TABLE_COOP_POST", "phpbb_snahp_coop_post");

class CoopEventListener implements EventSubscriberInterface
{
    public $config;
    public $sauth;
    public $helper;

    public function __construct(
        config $config,
        user_auth $sauth,
        CoopHelper $helper
    ) {
        $this->config = $config;
        $this->sauth = $sauth;
        $this->helper = $helper;
        $this->userId = $this->sauth->userId;
    }

    public static function getSubscribedEvents()
    {
        return [
            "core.viewtopic_modify_post_row" => [["embedBbCode", 1]],
            "core.posting_modify_submit_post_after" => [["createForum", 1]],
        ];
    }

    public function createForum($event)
    {
        $data = $event["data"];
        $postId = (int) $data["post_id"];
        $replaced = $this->helper->hideCoopInPost($postId);
        if ($replaced) {
            $this->helper->createForum($postId);
        }
    }

    public function embedBbCode($event)
    {
        $postRow = $event["post_row"];
        $classname = "rx_coop";
        $postId = (int) $postRow["POST_ID"];
        $postRow["MESSAGE"] = $this->helper->makeBbCodeElement(
            $postRow["MESSAGE"],
            $classname,
            $postId
        );
        $event["post_row"] = $postRow;
    }
}
