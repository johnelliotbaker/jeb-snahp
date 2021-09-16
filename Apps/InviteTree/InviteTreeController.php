<?php
namespace jeb\snahp\Apps\InviteTree;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

// use phpbb\config\config;
// use phpbb\controller\helper;
// use phpbb\db\driver\driver_interface;
// use phpbb\install\helper\container_factory;
// use phpbb\request\request_interface;
// use phpbb\template\template;
// use phpbb\user;
// use jeb\snahp\Apps\InviteTree\InviteTreeHelper;
// use jeb\snahp\core\auth\user_auth;

class InviteTreeController
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
        $this->sauth->reject_non_dev("Error Code: 937a1bd8f2");
    }

    public function view()
    {
        $users = $this->helper->getCompleteTree();
        $this->template->assign_vars([
            "USERS" => $users,
        ]);
        return $this->phpHelper->render("@jeb_snahp/invite_tree/base.html");
    }
}
