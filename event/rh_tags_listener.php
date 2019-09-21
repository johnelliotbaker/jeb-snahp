<?php

/*{{{*/
/**
 *
 * snahp. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2018
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace jeb\snahp\event;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class rh_tags_listener implements EventSubscriberInterface
{
    protected $sauth;
    public function __construct(
        $sauth
    )/*{{{*/
    {
        $this->sauth = $sauth;
    }/*}}}*/

    static public function getSubscribedEvents()/*{{{*/
    {
        return [
            'robertheim.topictags.modify_groupset_permission' => [
                ['modify_tags_permission', 0],
            ],
        ];
    }/*}}}*/

    public function modify_tags_permission($event, $event_name)/*{{{*/
    {
        $permission = $event['permission'];
        if ($this->sauth->user_belongs_to_groupset(null, 'Red Team'))
        {
            $permission = true;
            $event['permission'] = $permission;
        }
    }/*}}}*/

}
