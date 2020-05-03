<?php
namespace jeb\snahp\Apps\Boilerplate;

use \Symfony\Component\HttpFoundation\Response;
use \Symfony\Component\HttpFoundation\JsonResponse;
use jeb\snahp\Apps\Boilerplate\BoilerplateHelper;

class BoilerplateController
{
    protected $db;/*{{{*/
    protected $user;
    protected $config;
    protected $request;
    protected $template;
    protected $container;
    protected $helper;
    protected $tbl;
    protected $sauth;
    protected $myHelper;
    public function __construct(
        $db,
        $user,
        $config,
        $request,
        $template,
        $container,
        $helper,
        $tbl,
        $sauth,
        $myHelper
    ) {
        $this->db = $db;
        $this->user = $user;
        $this->config = $config;
        $this->request = $request;
        $this->template = $template;
        $this->container = $container;
        $this->helper = $helper;
        $this->tbl = $tbl;
        $this->sauth = $sauth;
        $this->myHelper = $myHelper;
        $this->userId = (int) $this->user->data['user_id'];
        $this->sauth->reject_anon('Error Code: a5e8ee80c7');
    }/*}}}*/

    public function retrieveView($id)/*{{{*/
    {
        $methodName = $this->request->server('REQUEST_METHOD', 'GET');
        if ($methodName != 'GET') {
            return new JsonResponse([]);
        }
        return $this->respondRetrieveAsJson($id);
    }/*}}}*/

    public function respondRetrieveAsJson($id)/*{{{*/
    {
        if ($id===0) {
            return new JsonResponse([]);
        }
        $row = $this->myHelper->getBoilerplateById($id);
        return $row ? new JsonResponse($row) : new JsonResponse([]);
    }/*}}}*/

    public function createListView()/*{{{*/
    {
        $methodName = $this->request->server('REQUEST_METHOD', 'GET');
        switch ($methodName) {
        case 'POST':
        case 'PATCH':
        case 'PUT':
            return new JsonResponse(["method"=>$methodName]);
        default:
            return new JsonResponse(["method"=>$methodName]);
        }
    }/*}}}*/
}
