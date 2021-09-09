<?php
namespace jeb\snahp\event;

use jeb\snahp\core\base;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class registration_listener extends base implements EventSubscriberInterface
{
    protected $table_prefix;
    public function __construct($table_prefix)
    {
        $this->table_prefix = $table_prefix;
    }

    public static function getSubscribedEvents()
    {
        return [
            "core.user_add_modify_data" => [["validate_invite", 0]],
            "core.user_add_after" => [["update_invite_after_user_addition", 0]],
        ];
    }

    public function validate_invite($event)
    {
        $keyphrase = $this->request->variable("invitation_code", "");
        if (!$keyphrase) {
            trigger_error(
                "Invitation code is required. Error Code: 757f279fcb"
            );
        }
        $invite_helper = new \jeb\snahp\core\invite_helper(
            $this->container,
            $this->user,
            $this->auth,
            $this->request,
            $this->db,
            $this->config,
            $this->helper,
            $this->template
        );
        $invite_data = $invite_helper->select_invite(
            $where = "keyphrase='$keyphrase'"
        );
        if (!$invite_data) {
            trigger_error(
                "Sorry, that invitation code is invalid. Error Code: 1f3e01afef"
            );
        }
        $invite_data = $invite_data[0];
        if (!$invite_data["b_active"]) {
            trigger_error(
                "Sorry, that invitation code has been deactivated. Error Code: d385410527"
            );
        }
    }

    public function update_invite_after_user_addition($event)
    {
        $user_id = $event["user_id"];
        $keyphrase = $this->request->variable("invitation_code", "");
        $invite_helper = new \jeb\snahp\core\invite_helper(
            $this->container,
            $this->user,
            $this->auth,
            $this->request,
            $this->db,
            $this->config,
            $this->helper,
            $this->template
        );
        $invite_helper->redeem_invite($keyphrase, $user_id);
    }
}
