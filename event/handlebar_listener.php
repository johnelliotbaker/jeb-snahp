<?php

namespace jeb\snahp\event;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class handlebar_listener implements EventSubscriberInterface
{
    protected $container;
    protected $config;
    protected $template;
    protected $helper;
    public function __construct(
        $container, $config, $template,
        $helper
    )/*{{{*/
    {
        $this->config = $config;
        $this->template = $template;
        $this->container = $container;
        $this->helper = $helper;
        $this->topic_data = [];
        $this->state = [];
    }/*}}}*/

    static public function getSubscribedEvents()/*{{{*/
    {
        return [
            'core.text_formatter_s9e_render_after' => [
                ['hide_spoiler', 0],
            ],
        ];
    }/*}}}*/

    public function hide_spoiler($event)/*{{{*/
    {
        $event['html'] = preg_replace_callback('#\|\|(.*?)\|\|#s', function($match){
            return '<span class="spoiler-wrapper"><span class="spoiler">' . $match[1] . '</span></span>';
        }, $event['html']);
    }/*}}}*/

}
