<?php

namespace jeb\snahp\event;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;


class spotlight_listener implements EventSubscriberInterface
{
    protected $db;
    protected $config;
    protected $user;
    protected $template;
    protected $container;
    protected $tbl;
    protected $sauth;
    protected $spotlight_helper;

    public function __construct(
        $db, $config, $user, $template, $container,
        $tbl,
        $sauth, $spotlight_helper
    )/*{{{*/
    {
        $this->db = $db;
        $this->config = $config;
        $this->user = $user;
        $this->template = $template;
        $this->container = $container;
        $this->tbl = $tbl;
        $this->sauth = $sauth;
        $this->spotlight_helper = $spotlight_helper;
        $this->user_id = $this->user->data['user_id'];
    }/*}}}*/

    static public function getSubscribedEvents()/*{{{*/
    {
        return [
            'core.viewtopic_modify_post_row' => [
                ['test', 1],
            ],
        ];
    }/*}}}*/

    public function test($event)/*{{{*/
    {
    }/*}}}*/

}
