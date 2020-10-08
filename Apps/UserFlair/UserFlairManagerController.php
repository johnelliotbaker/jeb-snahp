<?php
namespace jeb\snahp\Apps\UserFlair;

use \Symfony\Component\HttpFoundation\Response;
use \Symfony\Component\HttpFoundation\JsonResponse;

use \R as R;

class UserFlairManagerController
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
        // $this->sauth->reject_non_dev('Error Code: 298bc72da9');
    }/*}}}*/

    public function view()
    {
        $flair = $this->helper->getFlairs();
        $flair = $flair ? array_values($flair) : [];
        return new JsonResponse($flair);
    }

    public function manager()
    {
        $cfg = [
            'tpl_name' => '@jeb_snahp/user_flair_manager/base.html',
            'title' => 'User Flair Manager App',
        ];
        return $this->phpHelper->render($cfg['tpl_name'], $cfg['title']);
    }


    public function fixTypedataUnderscores()
    {
        $this->helper->fixTypedataUnderscores();
    }
}
