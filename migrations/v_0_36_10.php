<?php

namespace jeb\snahp\migrations;

use jeb\snahp\core\bbcodes_installer;
use phpbb\db\migration\container_aware_migration;

class v_0_36_10 extends container_aware_migration
{
    public function effectively_installed()
    {
        return false;
    }

    public static function depends_on()
    {
        return ['\jeb\snahp\migrations\v_0_36_9'];
    }

    public function update_schema()
    {
        return [
            "add_columns" => [
                $this->table_prefix . "users" => [
                    "snp_mute_created" => ["INT:11", 0],
                ],
            ],
        ];
    }

    public function revert_schema()
    {
        return [
            "drop_columns" => [
                $this->table_prefix . "users" => ["snp_mute_created"],
            ],
        ];
    }

    public function update_data()
    {
        return [];
    }
}
