<?php
namespace jeb\snahp\Apps\CustomBanner;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CustomBannerEventListener implements EventSubscriberInterface
{
    protected $helper;
    public function __construct($helper)
    {
        $this->helper = $helper;
    }

    public static function getSubscribedEvents()
    {
        return [
            "core.user_setup_after" => [
                ["showCustomBanners", 99], // Run this at high priority so BBCodeBanner can override
            ],
        ];
    }

    public function showCustomBanners($event)
    {
        if (!$this->helper->getVisibility()) {
            return;
        }
        // Injection in @jeb_snahp/event/overall_header_headerbar_after.html
        // Frontend in @jeb_snahp/banner/CustomBanner/base.html
        $now = time();
        if ($now > 1607990400 && $now < 1609459200) {
            $this->helper->selectBannerImage();
        }
    }
}
