<?php
namespace jeb\snahp\Apps\RequestManager;

use \Symfony\Component\HttpFoundation\Response;
use \Symfony\Component\HttpFoundation\JsonResponse;

class RequestManagerController
{
    protected $sauth;
    protected $helper;
    public function __construct(
        $sauth,
        $helper
    ) {
        $this->sauth = $sauth;
        $this->helper = $helper;
        $this->userId = (int) $this->user->data['user_id'];
        $this->sauth->reject_non_dev('Error Code: 241d70a113');
    }

    public function changeSolver($topicId, $solverId)/*{{{*/
    {
        $this->helper->changeSolver($topicId, $solverId);
        return new Response('Changed solver');
    }/*}}}*/

    public function changeSolverWithUsername($topicId, $solverUsername)/*{{{*/
    {
        if ($solverId = $this->sauth->userNameToUserId($solverUsername)) {
            $this->helper->changeSolver($topicId, $solverId);
            return new JsonResponse([ 'status' => 'success', ], 200);
        }
        return new JsonResponse(
            [
            'status' => 'FAILURE',
            'reason' => 'Could not resolve username'
            ], 404
        );
    }/*}}}*/
}
