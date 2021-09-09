<?php
namespace jeb\snahp\core\reputation;

class helper
{
    protected $db;
    protected $user;
    protected $auth;
    protected $config;
    protected $container;
    protected $tbl;
    protected $sauth;
    protected $user_id;
    protected $user_inventory;
    protected $product_class;
    public function __construct(
        $db,
        $user,
        $auth,
        $config,
        $container,
        $tbl,
        $sauth,
        $user_inventory,
        $product_class
    ) {
        $this->db = $db;
        $this->user = $user;
        $this->auth = $auth;
        $this->config = $config;
        $this->container = $container;
        $this->tbl = $tbl;
        $this->sauth = $sauth;
        $this->user_id = (int) $this->user->data["user_id"];
        $this->user_inventory = $user_inventory;
        $this->product_class = $product_class;
    }

    private function set_user_reputation_pool($user_id, $quantity)
    {
        $sql =
            "UPDATE " .
            USERS_TABLE .
            " SET snp_rep_n_available=${quantity}" .
            " WHERE user_id=${user_id}";
        $this->db->sql_query($sql);
    }

    public function set_min_for_users_with_upgrades($target)
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
            $this->set_user_reputation_pool($user_id, $quantity);
        }
    }

    public function set_min($target)
    {
        // A cron script runs this method to replenish user rep points
        $target = (int) $target;
        if (!$target) {
            return;
        }
        $sql =
            "UPDATE " .
            USERS_TABLE .
            " SET snp_rep_n_available={$target}" .
            " WHERE snp_rep_n_available < {$target}";
        $this->db->sql_query($sql);
        $this->config->set("snp_rep_giveaway_last_time", time());
        $this->set_min_for_users_with_upgrades($target);
    }
}
