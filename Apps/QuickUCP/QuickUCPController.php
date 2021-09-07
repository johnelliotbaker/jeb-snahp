<?php
namespace jeb\snahp\Apps\QuickUCP;

use \Symfony\Component\HttpFoundation\Response;
use \Symfony\Component\HttpFoundation\JsonResponse;

class QuickUCPController
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
        // $this->userId = 2;
        // TODO: Fix below
        // $this->sauth->reject_anon('Error Code: a5e8ee80c7');
    }

    public function generalSettings()/*{{{*/
    {
        if (getRequestMethod($this->request) === 'POST') {
            $data = getRequestData($this->request);
            $data = $this->helper->setSettings($this->userId, $data);
            return new JsonResponse($data);
        } else {
            try {
                $data = $this->helper->getSettings($this->userId);
                return new JsonResponse($data);
            } catch(\Exception $e) {
                return new JsonResponse(
                    [
                    'status'=> 400,
                    'message'=> $e->message,
                    ],
                    400
                );
            }
        }
        return new JsonResponse($data, 400);
    }/*}}}*/
}
