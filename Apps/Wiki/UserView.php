<?php

namespace jeb\snahp\Apps\Wiki;

use \Symfony\Component\HttpFoundation\JsonResponse;
use \R as R;

class UserView
{
    protected $request;
    protected $sauth;
    public function __construct($request, $sauth)
    {
        $this->request = $request;
        $this->sauth = $sauth;
    }/*}}}*/

    public function view()/*{{{*/
    {
        $userId = $this->sauth->userId;
        $isDev = $this->sauth->is_dev();
        // $isDev = true;
        $isKeeper = $isDev || $this->sauth->user_belongs_to_groupset($userId, 'Keepers');
        $data = [
            'userId' => (int) $userId,
            'isDev' => $isDev,
            'isKeeper' => $isKeeper,
        ];
        return new JsonResponse($data);
    }/*}}}*/
}
