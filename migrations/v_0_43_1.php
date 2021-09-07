<?php

// User AB Image Compare

namespace jeb\snahp\migrations;

use jeb\snahp\core\bbcodes_installer;
use phpbb\db\migration\container_aware_migration;

class v_0_43_1 extends container_aware_migration
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
            'add_columns' => [
                USERS_TABLE => [
                    'snp_ded_show_in_search' => ['BOOL', 0],
                ],
            ],
        ];
    }

    public function revert_schema()
    {
        return [
            'drop_columns' => [
                USERS_TABLE => ['snp_ded_show_in_search'],
            ],
        ];
    }

    public function update_data()
    {
        return [];
    }
}
