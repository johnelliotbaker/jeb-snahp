<?php

namespace jeb\snahp\migrations;

class v_0_21_2 extends \phpbb\db\migration\migration
{
    public function effectively_installed()
    { return false; }

    static public function depends_on()
    { return ['\jeb\snahp\migrations\v_0_21_0']; }

    public function update_schema()
    {
    }

    public function revert_schema()
    {
    }

    public function update_data()
    {
        return [
            ['config.add', ['snp_thanks_b_op', 1]],
        ];
    }

}
