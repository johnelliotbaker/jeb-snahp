<?php

/**
 * Create bank/shop transactions
 * */

namespace jeb\snahp\migrations;

class v_0_31_0 extends \phpbb\db\migration\migration
{
    public function effectively_installed()
    {
        return false;
    }

    public static function depends_on()
    {
        return ['\jeb\snahp\migrations\v_0_30_0'];
    }

    public function update_schema()
    {
        return [
            "add_tables" => [
                $this->table_prefix . "snahp_log" => [
                    "COLUMNS" => [
                        "id" => ["UINT", null, "auto_increment"],
                        "user_id" => ["INT:11", 0],
                        "type" => ["VCHAR:255", ""],
                        "name" => ["VCHAR:255", ""],
                        "created_time" => ["BINT", 0],
                    ],
                    "PRIMARY_KEY" => "id",
                ],
            ],
            "add_index" => [
                $this->table_prefix . "snahp_log" => [
                    "user_id" => ["user_id"],
                    "type" => ["type"],
                ],
            ],
        ];
    }

    public function revert_schema()
    {
        return [
            "drop_tables" => [$this->table_prefix . "snahp_log"],
        ];
    }

    public function update_data()
    {
        return [
            ["config.add", ["snp_log_b_master", 0]],
            ["config.add", ["snp_log_b_viewtopic", 0]],
            ["config.add", ["snp_log_b_posting", 0]],
            ["config.add", ["snp_log_b_viewforum", 0]],
            ["config.add", ["snp_log_b_search", 0]],
            ["config.add", ["snp_log_b_memberlist", 0]],
        ];
    }
}
