<?php
namespace jeb\snahp\Apps\Throttle;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

class ThrottleController
{
    protected $db;
    protected $user;
    protected $config;
    protected $request;
    protected $template;
    protected $container;
    protected $phpHelper;
    protected $tbl;
    protected $sauth;
    protected $helper;
    public function __construct(
        $db,
        $user,
        $config,
        $request,
        $template,
        $container,
        $phpHelper,
        $tbl,
        $sauth,
        $helper
    ) {
        $this->db = $db;
        $this->user = $user;
        $this->config = $config;
        $this->request = $request;
        $this->template = $template;
        $this->container = $container;
        $this->phpHelper = $phpHelper;
        $this->tbl = $tbl;
        $this->sauth = $sauth;
        $this->helper = $helper;
        $this->userId = $sauth->userId;
        $this->sauth->reject_non_dev("Error Code: 86a6e4ea0e");
    }

    public function settings()
    {
        $fields = [
            "master" => "snp_throttle_enable_master",
            "logging" => "snp_throttle_enable_logging",
            "throttle" => "snp_throttle_enable_throttle",
        ];
        $res = [];
        foreach ($fields as $key => $field) {
            $res[$key] = (int) $this->config[$field];
        }
        return new JsonResponse($res);
    }

    public function toplist()
    {
        $orderBy = $this->request->variable("order-by", "id");
        $toplist = $this->helper->getToplist($orderBy);
        return new JsonResponse($toplist);
    }

    public function disableMaster()
    {
        $this->sauth->reject_non_dev("Error Code: aaf7261f10");
        $this->helper->disableMaster();
        return new Response("Disabled Throttling");
    }

    public function enableMaster()
    {
        $this->sauth->reject_non_dev("Error Code: aaf7261f10");
        $this->helper->enableMaster();
        return new Response("Enabled Throttling");
    }

    public function disableLogging()
    {
        $this->sauth->reject_non_dev("Error Code: aaf7261f10");
        $this->helper->disableLogging();
        return new Response("Disabled Logging");
    }

    public function enableLogging()
    {
        $this->sauth->reject_non_dev("Error Code: aaf7261f10");
        $this->helper->enableLogging();
        return new Response("Enabled Logging");
    }

    public function disableThrottle()
    {
        $this->sauth->reject_non_dev("Error Code: aaf7261f10");
        $this->helper->disableThrottle();
        return new Response("Disabled Throttle");
    }

    public function enableThrottle()
    {
        $this->sauth->reject_non_dev("Error Code: aaf7261f10");
        $this->helper->enableThrottle();
        return new Response("Enabled Throttle");
    }
}
