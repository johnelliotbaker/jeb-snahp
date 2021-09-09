<?php
namespace jeb\snahp\Apps\TopicBump;

use \Symfony\Component\HttpFoundation\Response;
use \Symfony\Component\HttpFoundation\JsonResponse;

class TopicBumpController
{
    public function __construct(
        $request,
        $sauth,
        $helper
    ) {
        $this->request = $request;
        $this->sauth = $sauth;
        $this->helper = $helper;
        $this->userId = $sauth->userId;
        $this->sauth->reject_non_dev('Error Code: 0825a25783');
    }

    public function ban($username, $duration)
    {
        if ($duration < 0) {
            throw new \Exception('Duration must be greater than 0. Error Code: 2d4927907d');
        }
        $reason = $this->request->variable('reason', '');
        $returnMessage = ['status'=>'SUCCESS'];
        $status = 200;
        try {
            $this->helper->banUserByUsername($username, $duration, $reason);
        } catch (\Exception $e) {
            $returnMessage = ['status' => 'FAILURE', 'message' => $e->getMessage()];
            $status = 400;
        }
        return new JsonResponse($returnMessage, $status);
    }

    public function unban($username)
    {
        $this->helper->unBanUser($username);
        return new JsonResponse(['status'=>'SUCCESS']);
    }

    public function viewBumpUsers()
    {
        $data = $this->helper->getBumpUserToplist($this->request);
        return new JsonResponse($data);
    }

    public function editBan($username)
    {
        // Current Unused
        if (!$this->request->is_set_post('submit')) {
            return Response('', 400);
        }
        $data = $this->request->variable('data', '');
        $data = json_decode($data);
        $this->helper->editBanUser($username, $data);
        return new JsonResponse(['status'=>'SUCCESS']);
    }
}
