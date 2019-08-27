<?php
namespace jeb\snahp\controller;
use \Symfony\Component\HttpFoundation\Response;
use \Symfony\Component\HttpFoundation\JsonResponse;
use jeb\snahp\core\base;
use jeb\snahp\core\invite_helper;

class test
{
    protected $db;
    protected $user;
    protected $auth;
    protected $config;
    protected $request;
    protected $template;
    protected $container;
    protected $helper;
    protected $tbl;
    protected $sauth;

    public function __construct(
        $db, $user, $auth, $config, $request, $template, $container, $helper,
        $tbl,
        $sauth
    )/*{{{*/
    {
        $this->db = $db;
        $this->user = $user;
        $this->auth = $auth;
        $this->config = $config;
        $this->request = $request;
        $this->template = $template;
        $this->container = $container;
        $this->helper = $helper;
        $this->tbl = $tbl;
        $this->sauth = $sauth;
        $this->user_id = (int) $this->user->data['user_id'];
    }/*}}}*/

    public function handle($mode)/*{{{*/
    {
        switch ($mode)
        {
        case 'imgcompare':
            $cfg['tpl_name'] = '@jeb_snahp/imgcompare/base.html';
            $cfg['title'] = 'Test';
            return $this->respond_imgcompare($cfg);
        default:
            break;
        }
        trigger_error('test.php');
    }/*}}}*/

    public function respond_imgcompare($cfg)/*{{{*/
    {
        return $this->helper->render($cfg['tpl_name'], $cfg['title']);
    }/*}}}*/

}
