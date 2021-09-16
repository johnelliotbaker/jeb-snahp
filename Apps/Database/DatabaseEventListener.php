<?php
namespace jeb\snahp\Apps\Database;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class DatabaseEventListener implements EventSubscriberInterface
{
    public function __construct($config, $sauth, $helper)
    {
        $this->config = $config;
        $this->sauth = $sauth;
        $this->helper = $helper;
        $this->userId = $this->sauth->userId;
    }

    public static function getSubscribedEvents()
    {
        return [
                // 'core.viewtopic_modify_post_row' => [
                //     ['showDatabases', 1],
                // ],
            ];
    }

    public function showDatabases($event)
    {
    }
}
