<?php

// User Throttling

namespace jeb\snahp\migrations;

use jeb\snahp\core\bbcodes_installer;
use phpbb\db\migration\container_aware_migration;

class v_0_41_0 extends container_aware_migration
{
    public function effectively_installed()
    {
        return false;
    }

    public static function depends_on()
    {
        return ['\jeb\snahp\migrations\v_0_40_0'];
    }

    public function update_schema()
    {
        return [
            "add_tables" => [
                $this->table_prefix . "snahp_throttle" => [
                    "COLUMNS" => [
                        "id" => ["UINT", null, "auto_increment"],
                        "user_id" => ["INT:11", 0],
                        "ban_until" => ["INT:11", 0],
                        "total_flood" => ["INT:11", 0],
                        "monthly_flood" => ["INT:11", 0],
                        "total_visit" => ["INT:11", 0],
                        "monthly_visit" => ["INT:11", 0],
                        "data" => ["VCHAR:3000", ""],
                    ],
                    "PRIMARY_KEY" => "id",
                ],
            ],
            "add_index" => [
                $this->table_prefix . "snahp_throttle" => [
                    "total_flood" => ["total_flood"],
                    "montly_flood" => ["monthly_flood"],
                    "total_visit" => ["total_visit"],
                    "montly_visit" => ["monthly_visit"],
                ],
            ],
            "add_unique_index" => [
                $this->table_prefix . "snahp_throttle" => [
                    "user_id" => ["user_id"],
                ],
            ],
        ];
    }

    public function revert_schema()
    {
        return [
            "drop_tables" => [$this->table_prefix . "snahp_throttle"],
        ];
    }

    public function update_data()
    {
        return [
            ["config.add", ["snp_throttle_enable_master", 0]],
            ["config.add", ["snp_throttle_enable_logging", 0]],
            ["config.add", ["snp_throttle_enable_throttle", 0]],
        ];
    }
}
