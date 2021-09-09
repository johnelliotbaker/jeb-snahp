<?php
namespace jeb\snahp\controller\foe_blocker;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Forum foe_blocker MCP
 * */

class mcp
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
    protected $foe_helper;
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
        $foe_helper
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
        $this->foe_helper = $foe_helper;
        $this->user_id = $this->user->data["user_id"];
        $this->redirect_delay = 3;
        $this->redirect_delay_long = 6;
    }

    public function handle($mode)
    {
        $this->sauth->reject_non_dev("Error Code: 46f92d0481");
        switch ($mode) {
            case "manage":
                $cfg["tpl_name"] =
                    "@jeb_snahp/foe_blocker/component/mcp/manage.html";
                return $this->respondManage($cfg);
            case "save_mod_reason":
                return $this->respondSaveModReasonAsJson();
            case "toggle_permission":
                return $this->respondTogglePermissionTypeAsJson();
            case "toggle_perma_block":
                return $this->respondTogglePermaBlockAsJson();
            case "toggle_freeze":
                return $this->respondToggleFreezeAsJson();
        }
        trigger_error("Invalid mode. Error Code: c4116c5568");
    }

    private function respondSaveModReasonAsJson()
    {
        $blocker_id = $this->request->variable("blocker_id", 0);
        $blocked_id = $this->request->variable("blocked_id", 0);
        $mod_reason = $this->request->variable("mod_reason", "");
        if (!$blocker_id || !$blocked_id || !$mod_reason) {
            return new JsonResponse([
                "status" => "0",
                "reason" =>
                    "Must provide blocker_id, blocked_id, mod_reason. Error Code: 0b2fb2ea6e",
            ]);
        }
        if (
            $b_success = $this->foe_helper->update_mod_reason(
                $blocked_id,
                $blocker_id,
                $mod_reason
            )
                ? 1
                : 0
        ) {
            return new JsonResponse(["status" => $b_success]);
        }
        return new JsonResponse([
            "status" => $b_success,
            "reason" =>
                "Could not write to the database. Error Code: a63424124f",
        ]);
    }

    private function respondTogglePermissionTypeAsJson()
    {
        $blocker_id = $this->request->variable("blocker_id", 0);
        $blocked_id = $this->request->variable("blocked_id", 0);
        $permission_type = $this->request->variable("permission_type", "");
        if (!$blocker_id || !$blocked_id || !$permission_type) {
            return new JsonResponse([
                "status" => "failure",
                "reason" =>
                    "Must provide: blocked_id, blocker_id, permission_type. Error Code: e2ea3b53bf",
            ]);
        }
        if (
            $b_success = $this->foe_helper->toggle_permission_type(
                $blocked_id,
                $blocker_id,
                $permission_type
            )
                ? 1
                : 0
        ) {
            return new JsonResponse(["status" => $b_success]);
        }
        return new JsonResponse([
            "status" => $b_success,
            "reason" =>
                "Could not write to the database. Error Code: b0f81d6f84",
        ]);
    }

    private function respondToggleFreezeAsJson()
    {
        $blocker_id = $this->request->variable("blocker_id", 0);
        $blocked_id = $this->request->variable("blocked_id", 0);
        if (!$blocker_id || !$blocked_id) {
            return new JsonResponse([
                "status" => "failure",
                "reason" =>
                    "must provide valid blocked_id and blocker_id. Error Code: 0565f44a9f",
            ]);
        }
        if (
            $b_success = $this->foe_helper->toggle_freeze(
                $blocked_id,
                $blocker_id
            )
                ? 1
                : 0
        ) {
            return new JsonResponse(["status" => $b_success], 200);
        }
        return new JsonResponse(
            [
                "status" => $b_success,
                "reason" =>
                    "Could not write to the database. Error Code: ebbf27c64e",
            ],
            400
        );
    }

    private function respondTogglePermaBlockAsJson()
    {
        $blocker_id = $this->request->variable("blocker_id", 0);
        $blocked_id = $this->request->variable("blocked_id", 0);
        if (!$blocker_id || !$blocked_id) {
            return new JsonResponse([
                "status" => "failure",
                "reason" =>
                    "must provide valid blocked_id and blocker_id. Error Code: b5104e0394",
            ]);
        }
        if (
            $b_success = $this->foe_helper->toggle_perma_block(
                $blocked_id,
                $blocker_id
            )
                ? 1
                : 0
        ) {
            return new JsonResponse(["status" => $b_success]);
        }
        return new JsonResponse([
            "status" => $b_success,
            "reason" =>
                "Could not write to the database. Error Code: ebbf27c64e",
        ]);
    }

    private function respondManage($cfg)
    {
        $target_username = $this->request->variable("username", "");
        if (!$target_username) {
            trigger_error(
                "Must provide existing member username. Error Code: c612cef74e"
            );
        }
        $rowset = $this->foe_helper->select_blocked_data_by_username(
            $target_username
        );
        $rowset = $this->foe_helper->format_userlist($rowset);
        $this->template->assign_vars([
            "U_BLOCK" => $this->helper->route("jeb_snahp_routing.foe_blocker", [
                "mode" => "block",
            ]),
            "U_UNBLOCK" => $this->helper->route(
                "jeb_snahp_routing.foe_blocker",
                ["mode" => "unblock"]
            ),
            "ROWSET" => $rowset,
        ]);
        return $this->helper->render($cfg["tpl_name"], "Manage User Blocks");
    }
}
