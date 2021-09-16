<?php
namespace jeb\snahp\Apps\InviteTree;

// use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

// use phpbb\config\config;
// use jeb\snahp\Apps\InviteTree\InviteTreeHelper;
// use jeb\snahp\core\auth\user_auth;

class InviteTreeEventListener implements EventSubscriberInterface
{
    public $config;
    public $sauth;
    public $helper;
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
                //     ['showInviteTrees', 1],
                // ],
            ];
    }

    public function showInviteTrees($event)
    {
    }
}
