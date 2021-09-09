<?php

namespace jeb\snahp\migrations;

class v_0_27_2 extends \phpbb\db\migration\migration
{
    public function effectively_installed()
    {
        return false;
    }

    public static function depends_on()
    {
        return ['\jeb\snahp\migrations\v_0_27_1'];
    }

    public function update_schema()
    {
        return [
            'add_columns' => [
                GROUPS_TABLE => [
                    'snp_mydramalist_enable'  => ['BOOL', 0],
                ],
            ],
        ];
    }

    public function revert_schema()
    {
        return [
            'drop_columns' => [
                GROUPS_TABLE => [
                    'snp_mydramalist_enable',
                ],
            ],
        ];
    }

    public function update_data()
    {
        return [
            ['config.add', ['snp_pg_fid_mydramalist', '72,73,74,75']],
        ];
    }
}
