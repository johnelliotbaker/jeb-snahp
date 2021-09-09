<?php

namespace jeb\snahp\migrations;

class v_0_28_1 extends \phpbb\db\migration\migration
{
    public function effectively_installed()
    {
        return false;
    }

    public static function depends_on()
    {
        return ['\jeb\snahp\migrations\v_0_28_0'];
    }

    public function update_schema()
    {
        $allowed = serialize([0]);
        return [
            "add_columns" => [
                GROUPS_TABLE => [
                    "snp_emo_allowed_types" => ["VCHAR:255", $allowed],
                ],
            ],
        ];
    }

    public function revert_schema()
    {
        return [
            "drop_columns" => [
                GROUPS_TABLE => ["snp_emo_allowed_types"],
            ],
        ];
    }

    public function update_data()
    {
        return [];
    }
}
