<?php

namespace jeb\snahp\migrations;

class v_0_24_1 extends \phpbb\db\migration\migration
{
    public function effectively_installed()
    { return false; }

    static public function depends_on()
    { return ['\jeb\snahp\migrations\v_0_24_0']; }

    public function update_schema()
    {
        return [
            'add_columns' => [
                USERS_TABLE  => [
                    'snp_thanks_b_topic' => ['BOOL', 1],
                ],
            ],
        ];
    }

    public function revert_schema()
    {
        return [
            'drop_columns' => [
                USERS_TABLE => [
                    'snp_thanks_b_topic',
                ],
            ],
        ];
    }

    public function update_data()
    {
        return [];
    }

}
