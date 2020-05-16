<?php

// React Post Generators: IMDb & Discogs

namespace jeb\snahp\migrations;

use jeb\snahp\core\bbcodes_installer;
use phpbb\db\migration\container_aware_migration;

class v_0_37_1 extends container_aware_migration
{
    public function effectively_installed()
    {
        return false;
    }

    public static function depends_on()
    {
        return ['\jeb\snahp\migrations\v_0_37_0'];
    }

    public function update_schema()
    {
        return [];
    }

    public function revert_schema()
    {
        return [];
    }

    public function truncateThanksUsers()
    {
        global $table_prefix, $db;
        $sql = 'TRUNCATE ' . $table_prefix . 'thanks_users';
        $db->sql_query($sql);
    }

    public function toMediumText()
    {
        global $table_prefix, $db;
        $sql = 'ALTER TABLE ' . $table_prefix . 'thanks_users'
            . ' CHANGE COLUMN'
            . " `timestamps` `timestamps` MEDIUMTEXT NOT NULL";
        $db->sql_query($sql);
    }

    public function update_data()
    {
        return [
            ['custom', [
                [$this, 'truncateThanksUsers'],
            ]],
            ['custom', [
                [$this, 'toMediumText'],
            ]],
        ];
    }
}
