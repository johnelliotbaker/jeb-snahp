<?php

// Coop Mode

namespace jeb\snahp\migrations;

use jeb\snahp\core\bbcodes_installer;
use phpbb\db\migration\container_aware_migration;

class v_0_46_0 extends container_aware_migration
{
    public function effectively_installed()
    {
        return false;
    }

    public static function depends_on()
    {
        return ['\jeb\snahp\migrations\v_0_45_1'];
    }

    public function update_schema()
    {
        return [
            "add_tables" => [
                $this->table_prefix . "snahp_coop_forum" => [
                    "COLUMNS" => [
                        "id" => ["UINT", null, "auto_increment"],
                        "phpbb_post" => ["INT:11", 0],
                    ],
                    "PRIMARY_KEY" => ["id"],
                ],
                $this->table_prefix . "snahp_coop_topic" => [
                    "COLUMNS" => [
                        "id" => ["UINT", null, "auto_increment"],
                        "user" => ["INT:11", 0],
                        "visible" => ["VCHAR:20", "public"],
                        "title" => ["VCHAR:500", "public"],
                        "created" => ["INT:11", 0],
                        "modified" => ["INT:11", 0],
                        "phpbb_snahp_coop_forum_id" => ["INT:11", 0],
                    ],
                    "PRIMARY_KEY" => ["id"],
                    "KEYS" => [
                        "phpbb_snahp_coop_forum_id" => [
                            "INDEX",
                            "phpbb_snahp_coop_forum_id",
                        ],
                    ],
                ],
                $this->table_prefix . "snahp_coop_post" => [
                    "COLUMNS" => [
                        "id" => ["UINT", null, "auto_increment"],
                        "user" => ["INT:11", 0],
                        "text" => ["MTEXT", ""],
                        "visible" => ["VCHAR:20", "public"],
                        "created" => ["INT:11", 0],
                        "modified" => ["INT:11", 0],
                        "phpbb_snahp_coop_topic_id" => ["INT:11", 0],
                    ],
                    "PRIMARY_KEY" => ["id"],
                    "KEYS" => [
                        "phpbb_snahp_coop_topic_id" => [
                            "INDEX",
                            "phpbb_snahp_coop_topic_id",
                        ],
                    ],
                ],
            ],
            "add_unique_index" => [
                $this->table_prefix . "snahp_coop_forum" => [
                    "phpbb_post" => ["phpbb_post"],
                ],
            ],
        ];
    }

    public function revert_schema()
    {
        return [
            "drop_tables" => [
                $this->table_prefix . "snahp_coop_post",
                $this->table_prefix . "snahp_coop_topic",
                $this->table_prefix . "snahp_coop_forum",
            ],
        ];
    }

    public function update_data()
    {
        return [];
    }
}
