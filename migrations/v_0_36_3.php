<?php

// React Post Generators: IMDb & Discogs

namespace jeb\snahp\migrations;

use jeb\snahp\core\bbcodes_installer;
use phpbb\db\migration\container_aware_migration;

class v_0_36_3 extends container_aware_migration
{
    public function effectively_installed()
    {
        return false;
    }

    public static function depends_on()
    {
        return ['\jeb\snahp\migrations\v_0_36_2'];
    }

    public function update_schema()
    {
        return [ ];
    }

    public function revert_schema()
    {
        return [ ];
    }

    public function set_utf8mb4()
    {
        global $table_prefix, $db;
        $sql = 'ALTER TABLE ' . $table_prefix . 'snahp_tpl CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci';
        $db->sql_query($sql);
    }

    public function update_data()
    {
        return [
            ['custom', [[$this, 'set_utf8mb4']]],
        ];
    }
}
