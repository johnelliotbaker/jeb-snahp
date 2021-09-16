<?php
namespace jeb\snahp\Apps\QuickUCP;

class QuickUCPHelper
{
    public function __construct($db, $container, $tbl, $sauth)
    {
        $this->db = $db;
        $this->container = $container;
        $this->tbl = $tbl;
        $this->sauth = $sauth;
        // $this->paginator = $pageNumberPagination;
        // $this->QuerySetFactory = $QuerySetFactory;
        $this->userId = $sauth->userId;
    }

    public function getSettings($userId)
    {
        $fields =
            "user_id, username, user_colour, snp_search_hide_robot, snp_search_hide_deadlink";
        $User = $this->container->get("jeb.snahp.Apps.Core.Db.query.User");
        return $User->get($userId, ["fields" => $fields]);
    }

    public function setSettings($userId, $data)
    {
        foreach ($data as $key => $value) {
            $this->setSettingsBasedOnKey($userId, $key, $value);
        }
        return $this->getSettings($userId, $data);
    }

    public function setSettingsBasedOnKey($userId, $key, $value)
    {
        switch ($key) {
            case "snp_search_hide_deadlink":
                $helper = $this->container->get(
                    "jeb.snahp.Apps.DeadLinks.DeadLinksHelper"
                );
                $helper->setDeadlinksVisibilityInSearch($userId, $value);
                break;
            case "snp_search_hide_robot":
                $helper = $this->container->get(
                    "jeb.snahp.Apps.MrRobot.MrRobotHelper"
                );
                $helper->setMrRobotVisibilityInSearch($userId, $value);
                break;
            default:
        }
    }
}
