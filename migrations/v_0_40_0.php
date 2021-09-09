<?php

// The Quickening Update. Quick MCP and Quick UCP

namespace jeb\snahp\migrations;

use jeb\snahp\core\bbcodes_installer;
use phpbb\db\migration\container_aware_migration;

class v_0_40_0 extends container_aware_migration
{
    public function effectively_installed()
    {
        return false;
    }

    public static function depends_on()
    {
        return ['\jeb\snahp\migrations\v_0_39_0'];
    }

    public function update_schema()
    {
        return [
            "add_tables" => [
                $this->table_prefix . "snahp_bookmark" => [
                    "COLUMNS" => [
                        "id" => ["UINT", null, "auto_increment"],
                        "user" => ["INT:11", 0],
                        "topic" => ["INT:11", 0],
                        "type" => ["VCHAR:50", "basic"],
                        "url" => ["VCHAR:500", ""],
                        "name" => ["VCHAR:240", ""],
                    ],
                    "PRIMARY_KEY" => "id",
                ],
            ],
            "add_unique_index" => [
                $this->table_prefix . "snahp_bookmark" => [
                    "user_url" => ["user", "url"],
                ],
            ],
            "add_index" => [
                $this->table_prefix . "snahp_bookmark" => [
                    "user" => ["user"],
                    "type" => ["type"],
                    "topic" => ["topic"],
                ],
            ],
        ];
    }

    public function revert_schema()
    {
        return [
            "drop_tables" => [$this->table_prefix . "snahp_bookmark"],
        ];
    }

    public function update_data()
    {
        return [];
    }
}
