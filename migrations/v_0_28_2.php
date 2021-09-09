<?php

namespace jeb\snahp\migrations;

class v_0_28_2 extends \phpbb\db\migration\migration
{
    public function effectively_installed()
    {
        return false;
    }

    public static function depends_on()
    {
        return ['\jeb\snahp\migrations\v_0_28_1'];
    }

    public function update_schema()
    {
        return [
            'add_columns' => [
                $this->table_prefix . 'snahp_request_users'	=> [
                    'n_use_per_cycle_override'  => ['INT:11', -1],
                ],
            ],
        ];
    }

    public function revert_schema()
    {
        return [
            'drop_columns' => [
                $this->table_prefix . 'snahp_request_users'	=> [
                    'n_use_per_cycle_override',
                ],
            ],
        ];
    }

    public function update_data()
    {
        return [];
    }
}
