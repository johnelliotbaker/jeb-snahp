<?php
namespace jeb\snahp\Apps\UserRestrictor;

use \Symfony\Component\HttpFoundation\Response;
use \Symfony\Component\HttpFoundation\JsonResponse;

class UserRestrictorController
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
        $this->userId = (int) $this->user->data['user_id'];
        $this->sauth->reject_anon('Error Code: a5e8ee80c7');
    }

    public function restrict($userId)
    {
        $this->helper->restrictUser($userId);
        return new Response("Hello World, $userId");
    }

    public function free($userId)
    {
        $this->helper->freeUser($userId);
        return new Response("Hello World, $userId");
    }
}
