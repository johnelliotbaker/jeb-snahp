<?php

// User AB Image Compare

namespace jeb\snahp\migrations;

use jeb\snahp\core\bbcodes_installer;
use phpbb\db\migration\container_aware_migration;

class v_0_43_0 extends container_aware_migration
{
    public function effectively_installed()
    {
        return false;
    }

    public static function depends_on()
    {
        return ['\jeb\snahp\migrations\v_0_42_1'];
    }

    public function update_schema()
    {
        return [
            "add_tables" => [
                $this->table_prefix . "snahp_bump_user" => [
                    "COLUMNS" => [
                        "id" => ["UINT", null, "auto_increment"],
                        "user" => ["INT:11", 0],
                        "banned" => ["BOOL", 0],
                        "ban_start" => ["INT:11", 0],
                        "ban_end" => ["INT:11", 0],
                        "ban_reason" => ["TEXT_UNI", ""],
                        "ban_by" => ["INT:11", 0],
                    ],
                    "PRIMARY_KEY" => "id",
                ],
            ],
            "add_index" => [
                $this->table_prefix . "snahp_bump_user" => [
                    "banned" => ["banned"],
                ],
            ],
            "add_unique_index" => [
                $this->table_prefix . "snahp_bump_user" => [
                    "user" => ["user"],
                ],
            ],
        ];
    }

    public function revert_schema()
    {
        return [
            "drop_tables" => [$this->table_prefix . "snahp_bump_user"],
        ];
    }

    public function update_data()
    {
        return [];
    }
}
