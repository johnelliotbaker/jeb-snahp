<?php
namespace jeb\snahp\Apps\Userscript;

use Symfony\Component\HttpFoundation\Response;

class UserscriptController
{
    public function __construct($sauth, $helper)
    {
        $this->sauth = $sauth;
        $this->helper = $helper;
        $this->userId = $sauth->userId;
        $this->sauth->reject_anon("Error Code: a5e8ee80c7");
    }

    public function viewUserscript($uuid)
    {
        if ($scriptText = $this->helper->getScriptText($uuid)) {
            return new Response($scriptText);
        }
        trigger_error("The link has expired. Error Code: 14ab02a5a3");
    }
}
