<?php

// Foe Blocking

namespace jeb\snahp\migrations;

class v_0_36_6 extends \phpbb\db\migration\migration
{
    public function effectively_installed()
    { return false; }

    static public function depends_on()
    { return ['\jeb\snahp\migrations\v_0_36_5']; }

    public function update_schema()
    {
        return [
            'add_tables' => [
                $this->table_prefix . 'snahp_cron_log' => [
                    'COLUMNS' => [
                        'id'           => ['UINT', null, 'auto_increment'],
                        'user_id'      => ['INT:11', 0],
                        'type_name'    => ['VCHAR:512', ''],
                        'item_name'    => ['VCHAR:512', ''],
                        'status'       => ['VCHAR:100', ''],
                        'created_time' => ['INT:11', 0],
                        'data'         => ['MTEXT_UNI', ''],
                    ],
                    'PRIMARY_KEY' => 'id',
                ],
            ],
            'add_index'    => [
                $this->table_prefix . 'snahp_cron_log' => [
                    'item_name'    => ['item_name'],
                ],
            ],
        ];
    }

    public function revert_schema()
    {
        return [
            'drop_tables' => [
                $this->table_prefix . 'snahp_cron_log',
            ],
        ];
    }

    public function update_data()
    {
        return [ ];
    }

}
