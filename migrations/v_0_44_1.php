<?php

// Maze

namespace jeb\snahp\migrations;

use jeb\snahp\core\bbcodes_installer;
use phpbb\db\migration\container_aware_migration;

class v_0_44_1 extends container_aware_migration
{
    public function effectively_installed()
    {
        return false;
    }

    public static function depends_on()
    {
        return ['\jeb\snahp\migrations\v_0_44_0'];
    }

    public function update_schema()
    {
        return [
            "add_columns" => [
                USERS_TABLE => [
                    "snp_search_hide_robot" => ["BOOL", 0],
                    "snp_search_hide_deadlink" => ["BOOL", 1],
                ],
            ],
        ];
    }

    public function revert_schema()
    {
        return [
            "drop_columns" => [
                USERS_TABLE => [
                    "snp_search_hide_robot",
                    "snp_search_hide_deadlink",
                ],
            ],
        ];
    }

    public function update_data()
    {
        return [];
    }
}
