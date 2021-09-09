<?php
namespace jeb\snahp\Apps\Snowfall;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SnowfallEventListener implements EventSubscriberInterface
{
    protected $user;
    protected $config;
    protected $sauth;
    protected $helper;
    public function __construct($helper)
    {
        $this->helper = $helper;
    }

    public static function getSubscribedEvents()
    {
        return [
            "core.user_setup" => [["embedData", 0]],
        ];
    }

    public function embedData($event)
    {
        // Sets data-data attribute for the rx_snowfall_message
        // /var/www/forum/ext/jeb/snahp/styles/all/template/snowfall/base.html
        // Sets data-data attribute for the rx_snowfall
        // /var/www/forum/ext/jeb/snahp/styles/all/template/critical.html
        $this->helper->setTemplateVars();
    }
}
