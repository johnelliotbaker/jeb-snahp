<?php

// Add thanks module to ACP

namespace jeb\snahp\migrations;

class v_0_33_1 extends \phpbb\db\migration\migration
{
    public function effectively_installed()
    {
        return false;
    }

    public static function depends_on()
    {
        return ['\jeb\snahp\migrations\v_0_33_0'];
    }

    public function update_schema()
    {
        return [
            "add_columns" => [
                $this->table_prefix . "snahp_request" => [
                    "terminator_uid" => ["INT:11", 0],
                    "termination_reason" => ["VCHAR:1000", ""],
                ],
            ],
        ];
    }

    public function revert_schema()
    {
        return [];
    }

    public function update_data()
    {
        return [];
    }
}
