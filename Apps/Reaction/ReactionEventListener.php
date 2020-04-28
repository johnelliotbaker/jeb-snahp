<?php
namespace jeb\snahp\Apps\Reaction;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ReactionEventListener implements EventSubscriberInterface
{
    protected $user;
    protected $sauth;
    protected $myHelper;
    public function __construct(/*{{{*/
        $user,
        $sauth,
        $myHelper
    ) {
        $this->user = $user;
        $this->sauth = $sauth;
        $this->myHelper = $myHelper;
        $this->user_id = $this->user->data['user_id'];
    }/*}}}*/

    public static function getSubscribedEvents()/*{{{*/
    {
        return [
            'core.viewtopic_modify_post_row' => [
                ['showReactions', 1],
            ],
        ];
    }/*}}}*/

    public function showReactions($event)
    {
        $row = $event["post_row"];
        $postId = (int) $row['POST_ID'];
        $row["REACTIONS"] = ['postId' => $postId];
        $event["post_row"] = $row;
    }
}
