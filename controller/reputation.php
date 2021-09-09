<?php
namespace jeb\snahp\controller;

class reputation
{
    protected $base_url = "";
    protected $db;
    protected $user;
    protected $config;
    protected $request;
    protected $template;
    protected $container;
    protected $helper;
    protected $notification;
    protected $tbl;
    protected $sauth;
    protected $user_inventory;
    protected $product_class;
    public function __construct(
        $db,
        $user,
        $config,
        $request,
        $template,
        $container,
        $helper,
        $notification,
        $tbl,
        $sauth,
        $user_inventory,
        $product_class
    ) {
        $this->db = $db;
        $this->user = $user;
        $this->config = $config;
        $this->request = $request;
        $this->template = $template;
        $this->container = $container;
        $this->helper = $helper;
        $this->notification = $notification;
        $this->tbl = $tbl;
        $this->sauth = $sauth;
        $this->user_inventory = $user_inventory;
        $this->product_class = $product_class;
    }

    public function handle($mode)
    {
        $this->sauth->reject_anon();
        if (!$this->config["snp_rep_b_master"]) {
            trigger_error(
                "Reputation system has been disabled by the administrator. Error Code: 11f4a36948"
            );
        }
        $this->tbl = $this->container->getParameter("jeb.snahp.tables");
        $this->user_id = $this->user->data["user_id"];
        switch ($mode) {
            case "add":
                $cfg["tpl_name"] = "";
                $cfg["b_feedback"] = false;
                return $this->add($cfg);
                break;
            case "mcp_rep_giveaways":
                $this->sauth->reject_non_dev();
                $cfg["tpl_name"] =
                    "@jeb_snahp/mcp/component/mcp_rep_giveaways/base.html";
                $cfg["b_feedback"] = false;
                return $this->mcp_rep_giveaways($cfg);
            case "drn":
                $cfg["tpl_name"] = "";
                $cfg["b_feedback"] = false;
                return $this->delete_reps_notifications($cfg);
                break;
            default:
                trigger_error("Error Code: 22b8e0e0a5");
                break;
        }
    }

    public function mcp_rep_giveaways($cfg)
    {
        add_form_key("jeb_snp");
        if ($this->request->is_set_post("set_target")) {
            if (!check_form_key("jeb_snp")) {
                trigger_error("Error Code: f75bc84034");
            }
            $cfg["target"] = $this->request->variable("n_target", 0);
            $cfg["u_action"] = "/app.php/snahp/reputation/mcp_rep_giveaways/";
            $this->mcp_set($cfg);
            trigger_error("Error Code: 69eecfe7ac");
        }
        if ($this->request->is_set_post("set_minimum_target")) {
            if (!check_form_key("jeb_snp")) {
                trigger_error("Error Code: f75bc84034");
            }
            $cfg["target"] = $this->request->variable("n_minimum_target", 0);
            $cfg["u_action"] = "/app.php/snahp/reputation/mcp_rep_giveaways/";
            $this->mcp_set_min($cfg);
            trigger_error("Error Code: f26eb5f90b");
        }
        return $this->helper->render($cfg["tpl_name"]);
    }

    public function mcp_set($cfg)
    {
        $target = $cfg["target"];
        if (!$target) {
            meta_refresh(3, $cfg["u_action"]);
            trigger_error("Cannot set target to 0.");
        }
        $time = microtime(true);
        $sql = "UPDATE " . USERS_TABLE . " SET snp_rep_n_available={$target}";
        $this->db->sql_query($sql);
        $this->config->set("snp_rep_giveaway_last_time", time());
        $time = microtime(true) - $time;
        $time = sprintf("The procedure took %0.6f seconds.", $time);
        meta_refresh(3, $cfg["u_action"]);
        trigger_error(
            "All users' available reputation points were set to {$target}. {$time}"
        );
    }

    private function add_user_reputation_pool($user_id, $quantity)
    {
        $sql =
            "UPDATE " .
            USERS_TABLE .
            " SET snp_rep_n_available=${quantity}" .
            " WHERE user_id=${user_id}";
        $this->db->sql_query($sql);
    }

    public function mcp_set_min_for_users_with_upgrades($target)
    {
        // A cron script runs this method to replenish user rep points
        $name = "larger_rep_pool";
        $class_data = $this->product_class->get_product_class_by_name($name);
        $value = (int) $class_data["value"];
        $pcid = (int) $class_data["id"];
        $sql =
            "SELECT user_id, quantity FROM " .
            $this->tbl["user_inventory"] .
            " WHERE product_class_id=${pcid}";
        $result = $this->db->sql_query($sql);
        $rowset = $this->db->sql_fetchrowset($result);
        $this->db->sql_freeresult($result);
        foreach ($rowset as $row) {
            $user_id = $row["user_id"];
            $quantity = $row["quantity"] + $target;
            $this->add_user_reputation_pool($user_id, $quantity);
        }
    }

    public function mcp_set_min($cfg)
    {
        // A cron script runs this method to replenish user rep points
        $target = $cfg["target"];
        if (!$target) {
            meta_refresh(3, $cfg["u_action"]);
            trigger_error("Cannot set minimum target to 0.");
        }
        $time = microtime(true);
        $sql =
            "UPDATE " .
            USERS_TABLE .
            " SET snp_rep_n_available={$target}" .
            " WHERE snp_rep_n_available < {$target}";
        $this->db->sql_query($sql);
        $this->config->set("snp_rep_giveaway_last_time", time());
        $this->mcp_set_min_for_users_with_upgrades($target);
        $time = microtime(true) - $time;
        $time = sprintf("The procedure took %0.6f seconds.", $time);
        meta_refresh(3, $cfg["u_action"]);
        trigger_error(
            "Users with available reputation points less than {$target} was set to {$target}. {$time}"
        );
    }

    public function add($cfg)
    {
        return $this->confirm_add_reputation($cfg);
    }

    public function confirm_add_reputation($cfg)
    {
        $refresh = 2.5;
        $post_id = $this->request->variable("p", 0);
        if (!$post_id) {
            trigger_error(
                "You must provide a valid post id. Error Code: 5797460b62"
            );
        }
        $post_data = $this->select_post($post_id);
        if (!$post_data) {
            meta_refresh($refresh, "/viewtopic.php?p={$post_id}#{$post_id}");
            trigger_error("That post does not exist. Error Code: 889ed1c48b");
        }
        $u_action = "/app.php/snahp/reputation/add/?p=" . $post_id;
        $giver_id = $this->user_id;
        $poster_id = $post_data["poster_id"];
        $forum_id = $post_data["forum_id"];
        $topic_id = $post_data["topic_id"];
        add_form_key("jeb_snp");
        if ($this->request->is_set_post("cancel")) {
            meta_refresh(
                0,
                "/viewtopic.php?f={$forum_id}&t={$topic_id}&p={$post_id}#{$post_id}"
            );
            trigger_error("Error Code: b466a0bb05");
        }
        if ($this->request->is_set_post("confirm")) {
            $cfg = [
                "post_id" => $post_id,
                "topic_id" => $topic_id,
                "forum_id" => $forum_id,
                "giver_id" => $giver_id,
                "poster_id" => $poster_id,
                "post_data" => $post_data,
            ];
            $this->add_reputation($cfg);
        }
        $poster_data = $this->select_user($poster_id);
        $poster_username = $this->make_username($poster_data);
        $n_rep = $this->user->data["snp_rep_n_available"];
        $this->template->assign_vars([
            "POST_SUBJECT" => $post_data["post_subject"],
            "POSTER_USERNAME" => $poster_username,
            "N_REP_LEFT" => $n_rep,
        ]);
        return $this->helper->render(
            "@jeb_snahp/reputation/component/confirm_add/base.html"
        );
    }

    public function add_reputation($cfg)
    {
        $refresh = 2.5;
        // $this->delete_reputation_table();
        $post_id = $cfg["post_id"];
        $topic_id = $cfg["topic_id"];
        $forum_id = $cfg["forum_id"];
        $giver_id = $cfg["giver_id"];
        $poster_id = $cfg["poster_id"];
        $post_data = $cfg["post_data"];
        if ($poster_id == $giver_id) {
            meta_refresh(
                $refresh,
                "/viewtopic.php?f={$forum_id}&t={$topic_id}&p={$post_id}#{$post_id}"
            );
            trigger_error(
                "You cannot give yourself reputation points. But nice try. Error Code: 18c307e8e6"
            );
        }
        $n_avail = $this->user->data["snp_rep_n_available"];
        if ($n_avail < 1) {
            meta_refresh(
                $refresh,
                "/viewtopic.php?f={$forum_id}&t={$topic_id}&p={$post_id}#{$post_id}"
            );
            trigger_error(
                "You have used all of your reputation points. Error Code: 17d5424110"
            );
        }
        $reputation_data = $this->select_reputation(
            "post_id={$post_id} AND giver_id={$giver_id}"
        );
        if ($reputation_data) {
            meta_refresh(
                $refresh,
                "/viewtopic.php?f={$forum_id}&t={$topic_id}&p={$post_id}#{$post_id}"
            );
            trigger_error(
                "You have already given a reputation point for this post. Error Code: c65721c22f"
            );
        }
        $data = [
            "post_id" => $post_id,
            "giver_id" => $giver_id,
            "poster_id" => $poster_id,
            "time" => time(),
        ];
        $b_success = $this->insert_reputation($data);
        if ($b_success) {
            $this->update_giver_on_add($giver_id);
            $this->update_poster_on_add($poster_id);
            $rep_total = $this->select_rep_total_for_post($post_id, 0);
            $data["forum_id"] = $post_data["forum_id"];
            $data["topic_id"] = $post_data["topic_id"];
            $data["post_subject"] = $post_data["post_subject"];
            $data["rep_total"] = $rep_total;
            $b_notify = $this->is_notify($poster_id);
            if ($b_notify) {
                $this->notification->add_notifications(
                    "jeb.snahp.notification.type.reputation",
                    $data
                );
                $this->update_notifications(
                    "jeb.snahp.notification.type.reputation",
                    $post_id,
                    $rep_total
                );
            }
        }
        $n_avail -= 1;
        meta_refresh(
            $refresh,
            "/viewtopic.php?f={$forum_id}&t={$topic_id}&p={$post_id}#{$post_id}"
        );
        trigger_error("You now have {$n_avail} reputation points left.");
    }

    private function select_reputation($where)
    {
        $where = $this->db->sql_escape($where);
        $sql = "SELECT * FROM " . $this->tbl["reputation"] . " WHERE {$where}";
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        return $row;
    }

    private function select_reputation_count($type = "post_id", $id)
    {
        switch ($type) {
            case "post_id":
                $where = "post_id={$id}";
                break;
            case "poster_id":
                $where = "poster_id={$id}";
                break;
            case "giver_id":
                $where = "giver_id={$id}";
                break;
            default:
                $where = "post_id={$id}";
        }
        $sql =
            "SELECT COUNT(*) as count FROM " .
            $this->tbl["reputation"] .
            " WHERE {$where}";
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        if (!$row) {
            return 0;
        }
        return $row["count"];
    }

    private function insert_reputation($data)
    {
        $sql =
            "INSERT INTO " .
            $this->tbl["reputation"] .
            $this->db->sql_build_array("INSERT", $data);
        $this->db->sql_query($sql);
        return true;
    }

    private function delete_reputation_table()
    {
        $sql = "DELETE FROM " . $this->tbl["reputation"] . " WHERE 1=1";
        $this->db->sql_query($sql);
    }

    private function update_giver_on_add($giver_id)
    {
        $upd_strn =
            "snp_rep_n_given=snp_rep_n_given+1, snp_rep_n_available=snp_rep_n_available-1";
        $sql =
            "UPDATE " .
            USERS_TABLE .
            " SET " .
            $this->db->sql_escape($upd_strn) .
            " WHERE user_id={$giver_id}";
        $this->db->sql_query($sql);
    }

    private function update_poster_on_add($poster_id)
    {
        $upd_strn = "snp_rep_n_received=snp_rep_n_received+1";
        $sql =
            "UPDATE " .
            USERS_TABLE .
            " SET " .
            $this->db->sql_escape($upd_strn) .
            " WHERE user_id={$poster_id}";
        $this->db->sql_query($sql);
    }

    private function update_notifications(
        $notification_type_name,
        $post_id,
        $rep_total
    ) {
        // Previous code used delete + insert. Use proper update instead.
        $notification_type_id = (int) $this->notification->get_notification_type_id(
            $notification_type_name
        );
        $time = time();
        $sql =
            "SELECT notification_data FROM " .
            NOTIFICATIONS_TABLE .
            " WHERE item_id={$post_id} AND notification_type_id={$notification_type_id}";
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        $data = unserialize($row["notification_data"]);
        $data["rep_total"] = $rep_total;
        $data = $this->db->sql_escape(serialize($data));
        $sql =
            "UPDATE " .
            NOTIFICATIONS_TABLE .
            "
            SET notification_read=0, notification_time={$time}, notification_data='{$data}'
            WHERE item_id={$post_id} AND notification_type_id={$notification_type_id}";
        $this->db->sql_query($sql);
    }

    private function delete_reps_notifications()
    {
        $this->delete_reputation_notifications();
        $js = new \phpbb\json_response();
        $js->send(["status" => "success"]);
    }

    public function delete_reputation_notifications()
    {
        $sql =
            "SELECT * FROM " .
            NOTIFICATION_TYPES_TABLE .
            '
            WHERE notification_type_name="jeb.snahp.notification.type.reputation"';
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        $type_id = $row["notification_type_id"];
        $sql =
            "DELETE FROM " .
            NOTIFICATIONS_TABLE .
            '
            WHERE user_id=' .
            $this->user->data["user_id"] .
            '
        AND ' .
            $this->db->sql_in_set("notification_type_id", $type_id);
        $this->db->sql_query($sql);
    }

    private function is_notify($poster_id)
    {
        $type_name = "jeb.snahp.notification.type.reputation";
        $method = "notification.method.board";
        $sql =
            "SELECT * FROM " .
            $this->tbl["user_notifications"] .
            " WHERE item_type='{$type_name}' AND user_id={$poster_id} AND method='{$method}'";
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        if ($row && $row["notify"] == 0) {
            return false;
        }
        return true;
    }

    public function select_post($pid, $field = "*")
    {
        $sql =
            "SELECT " . $field . " FROM " . POSTS_TABLE . " WHERE post_id=$pid";
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        return $row;
    }

    public function select_user($user_id, $cooldown = 0)
    {
        $sql = "SELECT * FROM " . USERS_TABLE . " WHERE user_id=$user_id";
        $result = $this->db->sql_query($sql, $cooldown);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        return $row;
    }

    public function make_username($row)
    {
        $strn =
            '<a href="/memberlist.php?mode=viewprofile&u=' .
            $row["user_id"] .
            '" style="color: #' .
            $row["user_colour"] .
            '">' .
            $row["username"] .
            "</a>";
        return $strn;
    }

    public function select_rep_total_for_post($post_id, $cachetime = 1)
    {
        $tbl = $this->container->getParameter("jeb.snahp.tables");
        $sql =
            "SELECT COUNT(*) as count FROM " .
            $tbl["reputation"] .
            " WHERE post_id={$post_id}";
        $result = $this->db->sql_query($sql, $cachetime);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        if (!$row) {
            return 0;
        }
        return (int) $row["count"];
    }
}
