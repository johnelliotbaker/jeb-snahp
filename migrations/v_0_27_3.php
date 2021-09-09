<?php

namespace jeb\snahp\migrations;

class v_0_27_3 extends \phpbb\db\migration\migration
{
    public function effectively_installed()
    {
        return false;
    }

    public static function depends_on()
    {
        return ['\jeb\snahp\migrations\v_0_27_2'];
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
            ['config.add', ['snp_rep_giveaway_last_time', 0]],
            ['config.add', ['snp_rep_giveaway_minimum', 1]],
            ['config.add', ['snp_rep_giveaway_duration', 86400]],
            ['config.add', ['snp_rep_b_cron_giveaway', 1]],
        ];
    }
}
