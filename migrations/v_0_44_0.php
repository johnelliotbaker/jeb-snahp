<?php

// Maze

namespace jeb\snahp\migrations;

use jeb\snahp\core\bbcodes_installer;
use phpbb\db\migration\container_aware_migration;

class v_0_44_0 extends container_aware_migration
{
    public function effectively_installed()
    {
        return false;
    }

    public static function depends_on()
    {
        return ['\jeb\snahp\migrations\v_0_43_0'];
    }

    public function update_schema()
    {
        return [
            "add_tables" => [
                $this->table_prefix . "snahp_maze" => [
                    "COLUMNS" => [
                        "id" => ["UINT", null, "auto_increment"],
                        "text" => ["MTEXT", ""],
                        "post" => ["INT:11", 0],
                        "visible" => ["VCHAR:100", "hidden"],
                        "created_time" => ["INT:11", 0],
                        "dead_time" => ["INT:11", 0],
                    ],
                    "PRIMARY_KEY" => "id",
                ],
                $this->table_prefix . "snahp_maze_user" => [
                    "COLUMNS" => [
                        "id" => ["UINT", null, "auto_increment"],
                        "user" => ["INT:11", 0],
                        "log" => ["MTEXT", ""],
                        "phpbb_snahp_maze_id" => ["INT:11", 0],
                    ],
                    "PRIMARY_KEY" => "id",
                    "FOREIGN_KEY" => "phpbb_snahp_maze_id",
                ],
            ],
            "add_index" => [
                $this->table_prefix . "snahp_maze" => [
                    "post" => ["post"],
                    "visible" => ["post"],
                ],
                $this->table_prefix . "snahp_maze_user" => [
                    "user" => ["user"],
                    "phpbb_snahp_maze_id" => ["phpbb_snahp_maze_id"],
                ],
            ],
        ];
    }

    public function revert_schema()
    {
        return [
            "drop_tables" => [
                $this->table_prefix . "snahp_maze_user",
                $this->table_prefix . "snahp_maze",
            ],
        ];
    }

    public function update_data()
    {
        return [];
    }
}
