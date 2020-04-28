<?php
namespace jeb\snahp\Apps\Reaction;

use \Symfony\Component\HttpFoundation\Response;
use \Symfony\Component\HttpFoundation\JsonResponse;
use jeb\snahp\Apps\Reaction\ReactionHelper;

class ReactionController
{
    const SUCCESS = 'SUCCESS';
    const FAILURE = 'FAILURE';
    const POST_REACTION_LIMIT = 100;
    const TYPES = ['ack', 'music', 'more']; // Match vs rx:config/config.js

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
        $this->sauth->reject_anon('Error Code: 6572a0d111');
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
        $row = $this->myHelper->getReactionById($id);
        return $row ? new JsonResponse($row) : new JsonResponse([]);
    }/*}}}*/

    public function createListView()/*{{{*/
    {
        $methodName = $this->request->server('REQUEST_METHOD', 'GET');
        switch ($methodName) {
        case 'POST':
        case 'PATCH':
        case 'PUT':
            return $this->respondAddOrUpdateAsJson();
        default:
            return $this->respondReactionsAsJson();
        }
        return new JsonResponse(["method"=>$methodName]);
    }/*}}}*/

    private function respondAddOrUpdateAsJson()/*{{{*/
    {
        $userId = (int) $this->userId;
        // $userId = rand(48, 73);
        $type = $this->request->variable('type', '');
        $postId = $this->request->variable('postId', 0);
        $message = $this->request->variable('message', '');
        $this->validateAddReactionData($userId, $postId, $type);
        $this->myHelper->addOrUpdate($userId, $type, $postId, $message);
        return new JsonResponse(['status' => $this::SUCCESS]);
    }/*}}}*/

    private function validateAddReactionData($userId, $postId, $type)/*{{{*/
    {
        if (in_array(0, [$userId, $postId]) || !in_array($type, $this::TYPES)) {
            trigger_error("Invalid data. Error Code: c3d3f43902");
        }
    }/*}}}*/

    private function respondReactionsAsJson()/*{{{*/
    {
        $postId = $this->request->variable('p', 0);
        if (!$postId) {
            trigger_error("Invalid data. Error Code: 260e135020");
        }
        $rowset = $this->myHelper->getPostReactions(
            $postId,
            $this::POST_REACTION_LIMIT
        );
        return new JsonResponse($rowset);
    }/*}}}*/
}
