<?php

// React Post Generators: IMDb & Discogs

namespace jeb\snahp\migrations;

use jeb\snahp\core\bbcodes_installer;
use phpbb\db\migration\container_aware_migration;

class v_0_36_8 extends container_aware_migration
{
    public function effectively_installed()
    { return false; }

    static public function depends_on()
    { return ['\jeb\snahp\migrations\v_0_36_7']; }

    public function update_schema()
    {
        return [
            'add_columns' => [
                $this->table_prefix . 'users' => [
                    'snp_mute_topic' => ['BOOL', 0],
                    'snp_mute_reply' => ['BOOL', 0],
                ],
            ],
            'add_index'    => [
                $this->table_prefix . 'users' => [
                    'snp_mute_topic' => ['snp_mute_topic'],
                    'snp_mute_reply' => ['snp_mute_reply'],
                ],
            ],
        ];
    }

    public function revert_schema()
    {
        return [
            'drop_columns' => [
                $this->table_prefix . 'users' => [
                    'snp_mute_topic', 'snp_mute_reply'
                ],
            ],              
        ];
    }

    public function update_data()
    {
        return [
            ['config.add', ['snp_mute_b_master', 1]],
        ];
    }

}
