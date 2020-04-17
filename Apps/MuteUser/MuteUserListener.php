<?php

namespace jeb\snahp\Apps\MuteUser;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MuteUserListener implements EventSubscriberInterface
{
    protected $config;
    protected $user;
    protected $sauth;
    public function __construct(
        $config,
        $user,
        $sauth
    )/*{{{*/ {
        $this->config = $config;
        $this->user = $user;
        $this->sauth = $sauth;
        $this->user_id = $this->user->data['user_id'];
    }/*}}}*/

    public static function getSubscribedEvents()/*{{{*/
    {
        return [
            'core.modify_posting_auth' => [
                ['muteUser', 0],
            ],
        ];
    }/*}}}*/

    public function muteUser($event, $event_name)/*{{{*/
    {
        $mode = $event["mode"];
        $this->sauth->reject_muted_user($this->user_id, $mode);
        prn($user->data["snp_mute_reply"]);
        prn($user->data["snp_mute_topic"]);
    }/*}}}*/
}
