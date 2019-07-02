<?php

namespace jeb\snahp\migrations;

class v_0_27_1 extends \phpbb\db\migration\migration
{
    public function effectively_installed()
    { return false; }

    static public function depends_on()
    { return ['\jeb\snahp\migrations\v_0_27_0']; }

    public function update_schema()
    {
        return [
            'add_columns' => [
                USERS_TABLE => [
                    'snp_rep_b_profile'  => ['BOOL', 1],
                    'snp_achi_b_avatar'  => ['BOOL', 1],
                    'snp_achi_b_profile' => ['BOOL', 1],
                ],
            ],
        ];
    }

    public function revert_schema()
    {
        return [
            'drop_columns' => [
                USERS_TABLE => [
                    'snp_rep_b_profile',
                    'snp_achi_b_avatar',
                    'snp_achi_b_profile',
                ],
            ],
        ];
    }

    public function update_data()
    {
        return [
        ];
    }

}
