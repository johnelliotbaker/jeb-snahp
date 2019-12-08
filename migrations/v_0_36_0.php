<?php

// Foe Blocking

namespace jeb\snahp\migrations;

class v_0_36_0 extends \phpbb\db\migration\migration
{
    public function effectively_installed()
    { return false; }

    static public function depends_on()
    { return ['\jeb\snahp\migrations\v_0_35_2']; }

    public function update_schema()
    {
        return [
            'add_tables' => [
                $this->table_prefix . 'snahp_giveaways' => [
                    'COLUMNS' => [
                        'id'           => ['UINT', null, 'auto_increment'],
                        'user_id' => ['INT:11', 0],
                        'type_name'    => ['VCHAR:512', ''],
                        'item_name'    => ['VCHAR:512', ''],
                        'created_time' => ['INT:11', 0],
                        'data'         => ['MTEXT_UNI', ''],
                    ],
                    'PRIMARY_KEY' => 'id',
                ],
            ],
            'add_index'    => [
                $this->table_prefix . 'snahp_giveaways' => [
                    'user_id'      => ['user_id'],
                    'type_name'    => ['type_name'],
                    'item_name'    => ['item_name'],
                    'created_time' => ['created_time'],
                ],
            ],
        ];
    }

    public function revert_schema()
    {
        return [
            'drop_tables' => [
                $this->table_prefix . 'snahp_giveaways',
            ],
        ];
    }

    public function update_data()
    {
        return [
            ['config.add', ['snp_giv_b_master', 1]],
            ['config.add', ['snp_giv_cycle_time', 86400]],
            ['config.add', ['snp_giv_injection_post_id', 548964]],
        ];
    }

}
