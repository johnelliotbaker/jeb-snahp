<?php
namespace jeb\snahp\Apps\MassIndexer;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class BoilerplateEventListener implements EventSubscriberInterface
{
    protected $user;
    protected $config;
    protected $sauth;
    protected $myHelper;
    public function __construct(/*{{{*/
        $user,
        $config,
        $sauth,
        $myHelper
    ) {
        $this->user = $user;
        $this->config = $config;
        $this->sauth = $sauth;
        $this->myHelper = $myHelper;
        $this->user_id = $this->user->data['user_id'];
    }/*}}}*/

    public static function getSubscribedEvents()/*{{{*/
    {
        return [
            'core.viewtopic_modify_post_row' => [
                ['showBoilerplates', 1],
            ],
        ];
    }/*}}}*/

    public function showBoilerplates($event)
    {
    }
}
