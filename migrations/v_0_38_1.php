<?php

// React Post Generators: IMDb & Discogs

namespace jeb\snahp\migrations;

use jeb\snahp\core\bbcodes_installer;
use phpbb\db\migration\container_aware_migration;

class v_0_38_1 extends container_aware_migration
{
    public function effectively_installed()
    {
        return false;
    }

    public static function depends_on()
    {
        return ['\jeb\snahp\migrations\v_0_38_0'];
    }

    public function update_schema()
    {
        return [
            "add_columns" => [
                TOPICS_TABLE => [
                    "snp_ded_b_dead" => ["TINT:1", 0],
                    "snp_search_enhance" => ["TINT:1", 0],
                ],
            ],
        ];
    }

    public function revert_schema()
    {
        return [
            "drop_columns" => [
                TOPICS_TABLE => ["snp_ded_b_dead", "snp_search_enhance"],
            ],
        ];
    }

    public function update_data()
    {
        return [];
    }
}
