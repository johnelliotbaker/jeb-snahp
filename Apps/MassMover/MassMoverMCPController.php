<?php
namespace jeb\snahp\Apps\MassMover;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

class MassMoverMCPController
{
    protected $db;
    protected $user;
    protected $config;
    protected $request;
    protected $template;
    protected $container;
    protected $helper;
    protected $tbl;
    protected $sauth;
    protected $myHelper;
    protected $formHelper;
    public function __construct(
        $db,
        $user,
        $config,
        $request,
        $template,
        $container,
        $helper,
        $tbl,
        $sauth,
        $myHelper,
        $formHelper
    ) {
        $this->db = $db;
        $this->user = $user;
        $this->config = $config;
        $this->request = $request;
        $this->template = $template;
        $this->container = $container;
        $this->helper = $helper;
        $this->tbl = $tbl;
        $this->sauth = $sauth;
        $this->myHelper = $myHelper;
        $this->formHelper = $formHelper;
        $this->userId = (int) $this->user->data["user_id"];
    }

    public function handle($mode)
    {
        $this->sauth->reject_non_dev("Error Code: 997d304b9d");
        switch ($mode) {
            case "main":
                $cfg["tpl_name"] = "@jeb_snahp/mass_mover/base.html";
                $cfg["title"] = "Mass Mover V3";
                return $this->respondMassMover($cfg);
            default:
                break;
        }
        trigger_error(
            "Nothing to see here. Move along. Error Code: c8e92b5051"
        );
    }

    public function respondMassMover($cfg)
    {
        $required = [
            "username" => "",
            "quantity" => 1,
            "from_forum_id" => 4,
            "to_forum_id" => 23,
        ];
        $rv = $this->formHelper->getRequestVars($required);
        [$username, $fromForumId, $toForumId] = [
            $rv["username"],
            $rv["from_forum_id"],
            $rv["to_forum_id"],
        ];
        $start = 0;
        $limit = $rv["quantity"];
        $userTopics = $this->myHelper->getUserTopics(
            $username,
            $fromForumId,
            $start,
            $limit
        );
        if ($this->request->is_set_post("move")) {
            if ($this->myHelper->moveTopics($userTopics, $toForumId)) {
                $userTopics = [];
            }
        }
        $action = $this->helper->route("jeb_snahp_routing.mass_mover.mcp", [
            "mode" => "main",
        ]);
        $this->_embedSubforumSelector("FROM_FORUM", $fromForumId);
        $this->_embedSubforumSelector("TO_FORUM", $toForumId);
        $rv["action"] = $action;
        $rv["topics_list"] = $userTopics;
        $this->formHelper->setTemplateVars($rv);
        return $this->helper->render($cfg["tpl_name"], $cfg["title"]);
    }

    private function _embedSubforumSelector($varname, $selectId)
    {
        $subforumSelectorHTML = $this->myHelper->makeSubforumSelectorHTML(
            $selectId
        );
        $this->template->assign_var($varname, $subforumSelectorHTML);
    }
}
