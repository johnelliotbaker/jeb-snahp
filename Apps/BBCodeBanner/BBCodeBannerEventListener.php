<?php
namespace jeb\snahp\Apps\BBCodeBanner;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class BBCodeBannerEventListener implements EventSubscriberInterface
{
    protected $helper;
    public function __construct($helper)
    {
        $this->helper = $helper;
    }

    public static function getSubscribedEvents()
    {
        return [
            "core.viewtopic_assign_template_vars_before" => [
                ["showBBCodeBanners", 1],
            ],
            "core.modify_format_display_text_before" => [
                ["processBannerInPreview", 2],
            ],
        ];
    }

    public function processBannerInPreview($event)
    {
        $text = $event["text"];
        $this->helper->embedBannerInPreview($text);
    }

    public function showBBCodeBanners($event)
    {
        // Injection in @jeb_snahp/event/overall_header_headerbar_after.html
        // Frontend in @jeb_snahp/banner/BBCodeBanner/base.html
        $this->helper->embedBannerImageURL($event["topic_data"]);
    }
}
