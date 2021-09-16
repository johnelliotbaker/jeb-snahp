<?php
namespace jeb\snahp\Apps\Maze;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

class MazeController
{
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
        // $this->sauth->reject_anon("Error Code: a5e8ee80c7");
    }

    public function view($postId)
    {
        $userId = $this->userId;
        $userTypes = $this->helper->getUserTypes($userId, $postId);
        // DEBUG
        // $postId = 21609;
        // $userId = 63;
        // $userTypes = ["poster"];
        try {
            if (in_array("mod", $userTypes)) {
                $data = $this->helper->getModData($postId, $userTypes);
            } elseif (in_array("poster", $userTypes)) {
                $data = $this->helper->getOpData($userId, $postId, $userTypes);
            } else {
                $data = $this->helper->getPublicData(
                    $userId,
                    $postId,
                    $userTypes
                );
            }
        } catch (\Exception $e) {
            $data = ["user_types" => $userTypes];
        }
        return new JsonResponse($data);
    }

    public function viewPrivateMaze($mazeId)
    {
        $userId = $this->userId;
        // Additionally, throws if no maze or not a private maze
        $privateMaze = $this->helper->getPrivateMaze($mazeId, ["text"]);
        // Additionally, throws if maze user doesn't exist for the maze
        $this->helper->logPrivateAccess($userId, $mazeId);
        return new JsonResponse($privateMaze);
    }

    public function viewUsersInMazes($mazeIds)
    {
        $data = $this->helper->viewUsersInMazes($mazeIds);
        return new JsonResponse($data);
    }

    public function deleteMaze($mazeId)
    {
        $this->sauth->reject_non_dev("Error Code: 6cc760193c");
        $this->helper->deleteMaze($mazeId);
        return new Response();
    }

    public function createMazeInPost($postId)
    {
        $this->sauth->reject_non_dev("Error Code: f1df942cd7");
        $this->helper->createMazeInPost($postId);
        return new Response();
    }

    public function removeMazeFromPost($mazeId)
    {
        $this->helper->removeMazeFromPost($mazeId);
        return new Response();
    }

    public function markPost($postId)
    {
        $this->helper->markPost($postId);
        return new Response();
    }

    public function unMarkPost($postId)
    {
        $this->helper->unMarkPost($postId);
        return new Response();
    }
}
