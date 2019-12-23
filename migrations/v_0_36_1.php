<?php

// Foe Blocking

namespace jeb\snahp\migrations;

class v_0_36_1 extends \phpbb\db\migration\migration
{
    public function effectively_installed()
    { return false; }

    static public function depends_on()
    { return ['\jeb\snahp\migrations\v_0_36_0']; }

    public function update_schema()
    {
        return [
            'add_columns' => [
                GROUPS_TABLE => [
                    'snp_discogs_enable'  => ['BOOL', 0],
                ],
            ],
        ];
    }

    public function revert_schema()
    {
        return [
            'drop_columns' => [
                GROUPS_TABLE => [
                    'snp_discogs_enable',
                ],
            ],
        ];
    }

    public function update_data()
    {
        return [
            ['config.add', ['snp_pg_fid_discogs', '34,35,36,58,63']],
        ];
    }

}
