<?php

namespace jeb\snahp\Apps\CustomBanner;

use \Symfony\Component\HttpFoundation\Response;

class CustomBannerController
{
    protected $sauth;
    protected $helper;
    public function __construct(
        $sauth,
        $helper
    ) {
        $this->sauth = $sauth;
        $this->helper = $helper;
        $this->sauth->reject_anon('Error Code: 653e0a6cdc');
    }

    public function toggle()/*{{{*/
    {
        $action = $this->helper->getVisibility() ? "Disabled" : "Enabled";
        $this->helper->toggleVisibility();
        meta_refresh(1, '/');
        trigger_error("$action Seasonal Theme");
    }/*}}}*/
}
