<?php
namespace jeb\snahp\Apps\DeadLinks;

class DeadLinksHelper
{
    public function __construct($config, $sauth, $User)
    {
        $this->config = $config;
        $this->sauth = $sauth;
        $this->User = $User;
        $this->userId = $sauth->userId;
    }

    public function appendGraveyardToExFidAry($exFidAry)
    {
        $fidGraveyard =
            [
                (int) unserialize($this->config["snp_cron_graveyard_fid"])[
                    "default"
                ],
            ] ?? [];
        return array_merge($exFidAry, $fidGraveyard);
    }

    public function setDeadlinksVisibilityInSearch($userId, $value)
    {
        $userId = (int) $userId;
        $value = (int) (bool) $value;
        $this->User->update($userId, ["snp_ded_show_in_search" => $value]);
    }

    public function getDeadlinksVisibilityInSearch($userId)
    {
        $userId = (int) $userId;
        $data = $this->User->get($userId, [
            "fields" => "snp_ded_show_in_search",
        ]);
        return $data["snp_ded_show_in_search"];
    }
}
