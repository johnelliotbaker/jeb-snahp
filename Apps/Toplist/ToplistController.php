<?php
namespace jeb\snahp\Apps\Toplist;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

class ToplistController
{
    protected $sauth;
    protected $helper;
    protected $phpbbHelper;
    public function __construct($sauth, $helper)
    {
        $this->sauth = $sauth;
        $this->helper = $helper;
        $this->userId = (int) $this->sauth->userId;
        $this->sauth->reject_non_dev("Error Code: 66af242b70");
    }

    public function blacklist($name, $userId)
    {
        $this->helper->blacklist($name, $userId);
        return new Response("${userId} blacklisted in ${name}");
    }

    public function whitelist($name, $userId)
    {
        $this->helper->whitelist($name, $userId);
        return new Response("${userId} whitelisted in ${name}");
    }
}
