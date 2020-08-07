<?php
namespace jeb\snahp\Apps\UserFlair;

use \Symfony\Component\HttpFoundation\Response;
use \Symfony\Component\HttpFoundation\JsonResponse;

use \R as R;

class UserFlairController
{
    protected $db;/*{{{*/
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
        $this->sauth->reject_non_dev('Error Code: 298bc72da9');
    }/*}}}*/

    public function resetFlair()/*{{{*/
    {
        include '/var/www/forum/ext/jeb/snahp/Apps/UserFlair/Resetters.php';
        $userdata = $this->container->getParameter('jeb.snahp.avatar.badge.users');
        $itemdata = $this->container->getParameter('jeb.snahp.avatar.badge.items');
        $r = new FlairResetter($userdata, $itemdata, $this->helper->flairModel);
        $r->reset();
        return new Response('...');
    }/*}}}*/

    public function resetType()/*{{{*/
    {
        include '/var/www/forum/ext/jeb/snahp/Apps/UserFlair/Resetters.php';
        $itemdata = $this->container->getParameter('jeb.snahp.avatar.badge.items');
        $r = new TypeResetter($itemdata, $this->helper->typeModel);
        $r->reset();
        return new Response('...');
    }/*}}}*/
}
