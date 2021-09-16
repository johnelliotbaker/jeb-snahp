<?php
namespace jeb\snahp\Apps\MrRobot;

// use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

// use phpbb\config\config;
// use jeb\snahp\Apps\MrRobot\MrRobotHelper;
// use jeb\snahp\core\auth\user_auth;

class MrRobotEventListener implements EventSubscriberInterface
{
    public $config;
    public $sauth;
    public $helper;
    public function __construct($config, $sauth, $helper)
    {
        $this->config = $config;
        $this->sauth = $sauth;
        $this->helper = $helper;
        $this->userId = $this->sauth->userId;
    }

    public static function getSubscribedEvents()
    {
        return [
            "core.search_modify_param_before" => [["setupSearchParams", 0]],
            "core.search_modify_url_parameters" => [["hideMrRobotInSearch", 0]],
        ];
    }

    public function setupSearchParams($event)
    {
        // Setup for hideDeadlinksInSearch
        $this->searchKeywords = $event["keywords"];
    }

    public function hideMrRobotInSearch($event)
    {
        if ($this->searchKeywords) {
            $sqlWhere = $event["sql_where"];
            if (
                $sqlWhere &&
                $this->sauth->user->data["snp_search_hide_robot"]
            ) {
                $sqlWhere .= " AND p.poster_id <> 72";
                $event["sql_where"] = $sqlWhere;
            }
        }
    }
}
