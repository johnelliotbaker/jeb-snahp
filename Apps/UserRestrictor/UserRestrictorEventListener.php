<?php
namespace jeb\snahp\Apps\UserRestrictor;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class UserRestrictorEventListener implements EventSubscriberInterface
{
    protected $user;
    protected $config;
    protected $sauth;
    protected $helper;
    const MSG_PREFIX = "Your account has been restricted.";
    public function __construct($user, $config, $sauth, $helper)
    {
        $this->user = $user;
        $this->config = $config;
        $this->sauth = $sauth;
        $this->helper = $helper;
        $this->userId = $this->user->data["user_id"];
        $this->restricted = $this->user->data["snp_restricted"];
    }

    public static function getSubscribedEvents()
    {
        return [
            "core.posting_modify_submit_post_before" => [["restrictPost", 1]],
            "core.viewtopic_modify_post_row" => [["hidePostContent", 2]],
        ];
    }

    public function hidePostContent($event)
    {
        if (!$this->restricted || $this->sauth->is_dev()) {
            return;
        }
        $posterId = (int) $event["poster_id"];
        $canSee =
            $posterId === (int) $this->userId ||
            $this->sauth->user_belongs_to_groupset($posterId, "Staff");
        if (!$canSee) {
            $postRow = $event["post_row"];
            $message = &$postRow["MESSAGE"];
            $message =
                "Your account has been restricted. Error Code: c3ef2fc413";
            $event["post_row"] = $postRow;
        }
    }

    public function restrictPost($event)
    {
        $mode = $event["mode"];
        if (!$this->restricted) {
            return;
        }
        $data = $event["data"];
        $message = $data["message"];
        switch ($mode) {
            case "quote":
                $this->rejectPostMode($mode);
            // no break
            case "pm":
            case "post":
                $this->rejectLongPosts($message, 500);
                break;
            case "edit":
            case "reply":
                $this->rejectLongPosts($message, 250);
                break;
        }
    }

    public function rejectPostMode($mode)
    {
        print_r($MSG_PREFIX);
        trigger_error(
            self::MSG_PREFIX . " You cannot $mode. Error Code: 6aa8e872ef"
        );
    }

    public function rejectLongPosts($message, $max)
    {
        if (strlen($message) > $max) {
            trigger_error(
                self::MSG_PREFIX .
                    " You cannot make posts longer than ${max} characters. Error Code: fee369ce5a"
            );
        }
    }
}
