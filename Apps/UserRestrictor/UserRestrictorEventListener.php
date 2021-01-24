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
    public function __construct(/*{{{*/
        $user,
        $config,
        $sauth,
        $helper
    ) {
        $this->user = $user;
        $this->config = $config;
        $this->sauth = $sauth;
        $this->helper = $helper;
        $this->user_id = $this->user->data['user_id'];
    }/*}}}*/

    public static function getSubscribedEvents()/*{{{*/
    {
        return [
            'core.viewtopic_modify_post_row' => [
                ['showUserRestrictors', 1],
            ],
        ];
    }/*}}}*/

    public function showUserRestrictors($event)
    {
    }
}
