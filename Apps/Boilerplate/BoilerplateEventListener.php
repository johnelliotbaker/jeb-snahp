<?php
namespace jeb\snahp\Apps\Boilerplate;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class BoilerplateEventListener implements EventSubscriberInterface
{
    public function __construct(
        $config,
        $sauth,
        $helper
    ) {
        $this->config = $config;
        $this->sauth = $sauth;
        $this->helper = $helper;
        $this->userId = $this->sauth->userId;
    }

    public static function getSubscribedEvents()
    {
        return [
            // 'core.viewtopic_modify_post_row' => [
            //     ['showBoilerplates', 1],
            // ],
        ];
    }

    public function showBoilerplates($event)
    {
    }
}
