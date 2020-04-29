<?php

// React Post Generators: IMDb & Discogs

namespace jeb\snahp\migrations;

use jeb\snahp\core\bbcodes_installer;
use phpbb\db\migration\container_aware_migration;

class v_0_37_0 extends container_aware_migration
{
    public function effectively_installed()
    {
        return false;
    }

    public static function depends_on()
    {
        return ['\jeb\snahp\migrations\v_0_36_9'];
    }

    public function update_schema()
    {
        return [
            'add_tables' => [
                $this->table_prefix . 'snahp_reaction' => [
                    'COLUMNS' => [
                        'id'      => ['UINT', null, 'auto_increment'],
                        'type'    => ['VCHAR:20', ''],
                        'user_id' => ['INT:11', 0],
                        'post_id' => ['INT:11', 0],
                        'message' => ['VCHAR:140', ''],
                        'created' => ['INT:11', 0],
                    ],
                    'PRIMARY_KEY' => 'id',
                ],
                // $this->table_prefix . 'snahp_reaction_type' => [
                //     'COLUMNS' => [
                //         'id'   => ['UINT', null, 'auto_increment'],
                //         'name' => ['VCHAR:30', ''],
                //         'html' => ['VCHAR:1000', ''],
                //     ],
                //     'PRIMARY_KEY' => 'id',
                // ],
            ],
            'add_index'    => [
                $this->table_prefix . 'snahp_reaction' => [
                    'type'    => ['type'],
                    'post_id' => ['post_id'],
                    'user_id' => ['user_id'],
                ],
                // $this->table_prefix . 'snahp_reaction_type' => [
                //     'name' => ['name'],
                // ],
            ],
        ];
    }

    public function revert_schema()
    {
        return [
            'drop_tables' => [
                $this->table_prefix . 'snahp_reaction',
                // $this->table_prefix . 'snahp_reaction_type',
            ],
        ];
    }

    public function update_data()
    {
        return [
            ['config.add', ['snp_rxn_b_master', 1]],
        ];
    }
}
