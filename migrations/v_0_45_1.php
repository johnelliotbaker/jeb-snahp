<?php

// Throttle Visit

namespace jeb\snahp\migrations;

use jeb\snahp\core\bbcodes_installer;
use phpbb\db\migration\container_aware_migration;

class v_0_45_1 extends container_aware_migration
{
    public function effectively_installed()
    {
        return false;
    }

    public static function depends_on()
    {
        return ['\jeb\snahp\migrations\v_0_45_0'];
    }

    public function update_schema()
    {
        return [
        ];
    }

    public function revert_schema()
    {
        return [
        ];
    }

    public function update_data()
    {
        return [
            ["config.add", ["snp_throttle_enable_logging_visit", 1]],
        ];
    }
}
