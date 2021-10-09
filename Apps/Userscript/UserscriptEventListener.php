<?php
namespace jeb\snahp\Apps\Userscript;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class UserscriptEventListener implements EventSubscriberInterface
{
    public function __construct($sauth, $helper)
    {
        $this->sauth = $sauth;
        $this->helper = $helper;
        $this->userId = $this->sauth->userId;
    }

    public static function getSubscribedEvents()
    {
        return [
            "core.text_formatter_s9e_render_after" => [["parseUserscript", 1]],
        ];
    }

    public function parseUserscript($event)
    {
        $event["html"] = $this->helper->parseUserscript($event["html"]);
    }
}
