<?php

// Foe Blocking

namespace jeb\snahp\migrations;

class v_0_35_1 extends \phpbb\db\migration\migration
{
    public function effectively_installed()
    { return false; }

    static public function depends_on()
    { return ['\jeb\snahp\migrations\v_0_35_0']; }

    public function update_schema()
    {
        return [
            'add_columns' => [
                $this->table_prefix . 'snahp_foe' => [
                    'b_permanent'  => ['BOOL', 0],
                    'b_frozen'  => ['BOOL', 0],
                ],
            ],
            'add_index'    => [
                $this->table_prefix . 'snahp_foe' => [
                    'b_permanent' => ['b_permanent'],
                    'mod_id' => ['mod_id'],
                ],
            ],
        ];
    }

    public function revert_schema()
    {
        return [ ];
    }

    public function update_data()
    {
        return [ ];
    }

}
