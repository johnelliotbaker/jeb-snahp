<?php

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
    protected $request;
    protected $sauth;
    protected $rh_tags_helper;
    public function __construct(
        $request,
        $sauth,
        $rh_tags_helper
    ) {
        $this->request = $request;
        $this->sauth = $sauth;
        $this->rh_tags_helper = $rh_tags_helper;
    }

    public static function getSubscribedEvents()
    {
        return [
            'robertheim.topictags.modify_groupset_permission' => [
                ['modify_tags_permission', 0],
            ],
            'robertheim.topictags.get_tags_whitelist_before' => [
                ['get_tags_whitelist', 0],
            ],
        ];
    }

    public function get_tags_whitelist($event)
    {
        $tags = $event['tags'];
        $forum_id = $this->request->variable('f', 0);
        if (!$forum_id) {
            return false;
        }
        $a_groupname = $this->rh_tags_helper->get_groupnames_from_forum_map($forum_id);
        $tags = [];
        foreach ($a_groupname as $groupname) {
            if ($tmp = $this->rh_tags_helper->get_tagnames_from_group_map($groupname)) {
                sort($tmp);
                $tags = array_merge($tags, $tmp);
            }
        }
        $event['tags'] = $tags;
    }

    public function modify_tags_permission($event, $event_name)
    {
        $permission = $event['permission'];
        if ($this->sauth->user_belongs_to_groupset(null, 'JU+')) {
            $permission = true;
            $event['permission'] = $permission;
        }
    }
}
