<?php
namespace jeb\snahp\Apps\TopicUpdateTicker;

use \Symfony\Component\HttpFoundation\Response;
use \Symfony\Component\HttpFoundation\JsonResponse;

class TopicUpdateTickerController
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
        $row = $this->helper->getTopicUpdateTickerById($id);
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
