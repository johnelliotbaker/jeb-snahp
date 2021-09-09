<?php

namespace jeb\snahp\Apps\DeadLinks;

use Symfony\Component\HttpFoundation\JsonResponse;

class DeadLinksController
{
    protected $phpHelper;
    protected $sauth;
    public function __construct($phpHelper, $helper, $sauth)
    {
        $this->phpHelper = $phpHelper;
        $this->helper = $helper;
        $this->sauth = $sauth;
        $this->userId = (int) $this->sauth->userId;
        $this->sauth->reject_new_users("Error Code: 0beb743101");
    }

    public function view()
    {
        $cfg["tpl_name"] = "@jeb_snahp/deadlinks/rx_deadlinks.html";
        $cfg["title"] = "Deadlinks Reporter";
        return $this->phpHelper->render($cfg["tpl_name"], $cfg["title"]);
    }

    public function setDeadlinksVisibilityInSearch($value)
    {
        $this->helper->setDeadlinksVisibilityInSearch($this->userId, $value);
        $show = $this->helper->getDeadlinksVisibilityInSearch($this->userId);
        return new JsonResponse(["what" => $show]);
    }
}
