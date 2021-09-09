<?php
namespace jeb\snahp\Apps\Achievements;

class AchievementsHelper
{
    protected $db;
    protected $user;
    protected $template;
    protected $tbl;
    protected $sauth;
    public function __construct(
        $db,
        $tbl,
        $sauth,
        $pageNumberPagination,
        $QuerySetFactory,
        $ToplistHelper
    ) {
        $this->db = $db;
        $this->tbl = $tbl;
        $this->sauth = $sauth;
        $this->paginator = $pageNumberPagination;
        $this->QuerySetFactory = $QuerySetFactory;
        $this->ToplistHelper = $ToplistHelper;
        $this->userId = $sauth->userId;
    }

    public function getAchievements($userId)
    {
        $res = [];
        $rep = $this->ToplistHelper->getReputationToplist();
        $count = 0;
        foreach ($rep as $k => $v) {
            if ((int) $v['user_id'] === $userId) {
                $res['reputation'] = $k + 1;
                $count++;
                break;
            }
        }
        $req = $this->ToplistHelper->getRequestSolvedToplist();
        foreach ($req as $k => $v) {
            if ((int) $v['user_id'] === $userId) {
                $res['requests_solved'] = $k + 1;
                $count++;
                break;
            }
        }
        $thanks = $this->ToplistHelper->getThanksToplist();
        foreach ($thanks as $k => $v) {
            if ((int) $v['user_id'] === $userId) {
                $res['thanks'] = $k + 1;
                $count++;
                break;
            }
        }
        if ($count > 0) {
            $res['count'] = $count;
        }
        return $res;
    }
}
