<?php

namespace jeb\snahp\migrations;

class v_0_26_0 extends \phpbb\db\migration\migration
{
    public function effectively_installed()
    {
        return false;
    }

    public static function depends_on()
    {
        return ['\jeb\snahp\migrations\v_0_25_2'];
    }

    public function update_schema()
    {
        return [
            'add_tables' => [
                $this->table_prefix . 'snahp_achievements' => [
                    'COLUMNS' => [
                        'id'  => ['UINT', null, 'auto_increment'],
                        'type'  => ['VCHAR:255', 0],
                        'user_id'  => ['INT:11', 0],
                        'modified_time'  => ['INT:11', 0],
                    ],
                    'PRIMARY_KEY' => 'id',
                ],
            ],
            'add_index'    => [
                $this->table_prefix . 'snahp_achievements'  => [
                    'type' => ['type'],
                    'user_id' => ['user_id'],
                ],
            ],
            // 'add_unique_index'    => [
            //     $this->table_prefix . 'snahp_digg_slave'  => [
            //         'pair' => ['topic_id', 'user_id'],
            //     ],
            // ],
        ];
    }

    public function revert_schema()
    {
        return [
            'drop_tables' => [
                $this->table_prefix . 'snahp_achievements',
            ],
        ];
    }

    public function update_data()
    {
        return [
            ['config.add', ['snp_achi_b_master', 1]],
            ['config.add', ['snp_achi_b_show', 1]],
        ];
    }
}
