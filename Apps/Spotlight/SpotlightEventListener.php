<?php
namespace jeb\snahp\Apps\Spotlight;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SpotlightEventListener implements EventSubscriberInterface
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
            "core.index_modify_page_title" => [["embedSpotlight", 1]],
        ];
    }

    public function embedSpotlight($event)
    {
        $this->helper->embedSpotlight();
    }
}
