<?php
namespace jeb\snahp\Apps\Xmas;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class XmasEventListener implements EventSubscriberInterface
{
    protected $user;
    protected $config;
    protected $sauth;
    protected $helper;
    public function __construct($user, $config, $sauth, $helper)
    {
        $this->user = $user;
        $this->config = $config;
        $this->sauth = $sauth;
        $this->helper = $helper;
        $this->user_id = $this->user->data["user_id"];
    }

    public static function getSubscribedEvents()
    {
        return [
            "core.viewtopic_modify_post_row" => [["showXmass", 1]],
        ];
    }

    public function showXmass($event)
    {
        $poster_id = (int) $event["poster_id"];
        if (!in_array($poster_id, [2, 10414])) {
            return;
        }
        $post_row = $event["post_row"];
        $message = &$post_row["MESSAGE"];
        $message = preg_replace(
            "#_xmas_#",
            '<div class="rx_xmas"></div>',
            $message,
            1
        );
        $event["post_row"] = $post_row;
    }
}
