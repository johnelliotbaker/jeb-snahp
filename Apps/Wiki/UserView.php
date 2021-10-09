<?php
namespace jeb\snahp\Apps\Wiki;

use Symfony\Component\HttpFoundation\JsonResponse;

class UserView
{
    protected $request;
    protected $sauth;
    public function __construct($request, $sauth)
    {
        $this->request = $request;
        $this->sauth = $sauth;
        $this->sauth->reject_anon("Error Code: cee5910f65");
    }

    public function view()
    {
        // $data = [
        //     "userId" => (int) $this->sauth->userId,
        //     "isDev" => true,
        //     "isKeeper" => true,
        // ];
        // return new JsonResponse($data);
        $userId = $this->sauth->userId;
        $isDev = $this->sauth->is_dev();
        $isKeeper =
            $isDev ||
            $this->sauth->user_belongs_to_groupset($userId, "Keepers");
        $data = [
            "userId" => (int) $userId,
            "isDev" => $isDev,
            "isKeeper" => $isKeeper,
        ];
        return new JsonResponse($data);
    }
}
