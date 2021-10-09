<?php

// Throttle Visit

namespace jeb\snahp\migrations;

use jeb\snahp\core\bbcodes_installer;
use phpbb\db\migration\container_aware_migration;

class v_0_45_0 extends container_aware_migration
{
    public function effectively_installed()
    {
        return false;
    }

    public static function depends_on()
    {
        return ['\jeb\snahp\migrations\v_0_44_1'];
    }

    public function update_schema()
    {
        return [
            "add_tables" => [
                $this->table_prefix . "snahp_throttle_visit" => [
                    "COLUMNS" => [
                        "id" => ["UINT", null, "auto_increment"],
                        "user" => ["INT:11", 0],
                        "data" => ["MTEXT", ""],
                    ],
                    "PRIMARY_KEY" => "id",
                ],
            ],
            "add_index" => [
                $this->table_prefix . "snahp_throttle_visit" => [
                    "user" => ["user"],
                ],
            ],
        ];
    }

    public function revert_schema()
    {
        return [
            "drop_tables" => [
                $this->table_prefix . "snahp_throttle_visit",
            ],
        ];
    }

    public function update_data()
    {
        return [];
    }
}
