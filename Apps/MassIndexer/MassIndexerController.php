<?php
namespace jeb\snahp\Apps\MassIndexer;

use \Symfony\Component\HttpFoundation\Response;
use \Symfony\Component\HttpFoundation\JsonResponse;
use jeb\snahp\Apps\MassIndexer\MassIndexerHelper;

class MassIndexerController
{
    protected $db;
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
        $this->sauth->reject_non_dev('Error Code: 75ae9f597c');
    }

    public function unindexUser($username)
    {
        $username = (string) $username;
        $rootForum = $this->request->variable('f', 0);
        $total = $this->myHelper->unindexAllPostsByUsername(
            $username,
            $rootForum
        );
        return $this->standardJsonResponse($total);
    }

    public function unindexTopic($topicId)
    {
        $topicId = (int) $topicId;
        $total = $this->myHelper->unindexAllPostsByTopic($topicId);
        return $this->standardJsonResponse($total);
    }

    public function unindexForum($forumId)
    {
        $forumId = (int) $forumId;
        $total = $this->myHelper->unindexAllPostsByForum($forumId);
        return $this->standardJsonResponse($total);
    }

    public function unindexGraveyard()
    {
        $forumId = 23;
        $total = $this->myHelper->unindexAllPostsByForum($forumId);
        return $this->standardJsonResponse($total);
    }

    private function standardJsonResponse($total)
    {
        return new JsonResponse(
            [
                "status" => "SUCCESS",
                "total_processed" => $total,
            ]
        );
    }
}
