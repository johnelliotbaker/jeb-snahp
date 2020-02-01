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
        // Logic for hiding
        $hide = function($html) 
        {
            return preg_replace_callback('#\|\|(.*?)\|\|#s', function($match){
                return '<span class="spoiler-wrapper"><span class="spoiler">' . $match[1] . '</span></span>';
            }, $html);
        };
        // Decorator for ignoring code block
        $string_util = new \jeb\snahp\core\string_util();
        $event['html'] = $string_util->ignore_codeblock($hide)($event['html']);
    }/*}}}*/

}
