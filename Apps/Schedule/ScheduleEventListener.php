<?php
namespace jeb\snahp\Apps\Schedule;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ScheduleEventListener implements EventSubscriberInterface
{
    public $config;
    public $sauth;
    public $helper;
    public function __construct($helper)
    {
        $this->helper = $helper;
    }

    public static function getSubscribedEvents()
    {
        return [
            "core.page_footer_after" => [["run", 1]],
        ];
    }

    public function run($event)
    {
        $this->helper->resetReputationPool();
    }
}
