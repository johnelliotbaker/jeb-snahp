<?php
namespace jeb\snahp\Apps\UserFlair;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class UserFlairEventListener implements EventSubscriberInterface
{
    protected $helper;
    public function __construct($helper)
    {
        $this->helper = $helper;
    }

    public static function getSubscribedEvents()
    {
        return [
            "core.viewtopic_modify_post_row" => [["showUserFlairs", 1]],
        ];
    }

    public function showUserFlairs($event)
    {
        $postRow = $event["post_row"];
        $userId = $postRow["POSTER_ID"];
        $user = [
            "id" => $postRow["POSTER_ID"],
            "color" => $postRow["POST_AUTHOR_COLOUR"],
            "name" => $postRow["POST_AUTHOR"],
            // 'avatar' => $postRow['POSTER_AVATAR'],
        ];
        $data = $this->helper->makeHtml($user);
        $postRow["USER_FLAIR"] = $data;
        $event["post_row"] = $postRow;
    }
}
