<?php
namespace jeb\snahp\Apps\Emoticon;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class EmoticonEventListener implements EventSubscriberInterface
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
            "core.viewtopic_assign_template_vars_before" => [
                ["embedEmoticon", 1],
            ],
            "core.posting_modify_template_vars" => [["embedEmoticon", 1]],
        ];
    }

    public function embedEmoticon($event)
    {
        $this->helper->embedEmoticon($this->userId);
    }
}
