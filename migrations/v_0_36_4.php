<?php

// Foe Blocking

namespace jeb\snahp\migrations;

class v_0_36_4 extends \phpbb\db\migration\migration
{
    public function effectively_installed()
    {
        return false;
    }

    public static function depends_on()
    {
        return ['\jeb\snahp\migrations\v_0_36_3'];
    }

    public function update_schema()
    {
        return [
            "add_columns" => [
                $this->table_prefix . "snahp_tpl" => [
                    "priority" => ["TINT:2", 0],
                ],
            ],
            "add_index" => [
                $this->table_prefix . "snahp_tpl" => [
                    "priority" => ["priority"],
                ],
            ],
        ];
    }

    public function revert_schema()
    {
        return [
            "drop_columns" => [
                $this->table_prefix . "snahp_tpl" => ["priority"],
            ],
        ];
    }

    public function update_data()
    {
        return [];
    }
}
