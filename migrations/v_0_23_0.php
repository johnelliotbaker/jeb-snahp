<?php

namespace jeb\snahp\migrations;

class v_0_23_0 extends \phpbb\db\migration\migration
{
    public function effectively_installed()
    { return false; }

    static public function depends_on()
    { return ['\jeb\snahp\migrations\v_0_22_0']; }

    public function update_schema()
    {
        return [
            'add_tables' => [
                $this->table_prefix . 'snahp_tpl' => [
                    'COLUMNS' => [
                        'id'  => ['UINT', null, 'auto_increment'],
                        'user_id' => ['INT:10', 0],
                        'name' => ['VCHAR:20', ''],
                        'text' => ['TEXT_UNI', ''],
                    ],
                    'PRIMARY_KEY' => 'id',
                ],
            ],
            'add_unique_index'    => [
                $this->table_prefix . 'snahp_tpl'  => [
                    'uid_name'  => ['user_id', 'name'],
                ],
            ],
            'add_index'    => [
                $this->table_prefix . 'snahp_tpl'  => [
                    'user_id'  => ['user_id'],
                ],
            ],
        ];
    }

    public function revert_schema()
    {
        return [
            'drop_tables' => [
                $this->table_prefix . 'snahp_tpl',
            ],
        ];
    }

    public function update_data()
    {
        return [];
    }

}
