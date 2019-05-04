<?php

namespace jeb\snahp\migrations;

class v_0_21_0 extends \phpbb\db\migration\migration
{
    public function effectively_installed()
    { return false; }

    static public function depends_on()
    { return ['\jeb\snahp\migrations\v_0_20_0']; }

    public function update_schema()
    {
        return [
            'add_columns'	=> [
                GROUPS_TABLE  => [
                    'snp_req_b_solve' => ['BOOL', 0],
                ],
            ],
        ];
    }

    public function revert_schema()
    {
        return [
            'drop_columns'	=> [
                GROUPS_TABLE  => [
                    'snp_req_b_solve',
                ],
            ],
        ];
    }

    public function update_data()
    {
    }

}
