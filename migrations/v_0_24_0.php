<?php

namespace jeb\snahp\migrations;

class v_0_24_0 extends \phpbb\db\migration\migration
{
    public function effectively_installed()
    {
        return false;
    }

    public static function depends_on()
    {
        return ['\jeb\snahp\migrations\v_0_23_0'];
    }

    public function update_schema()
    {
        return [
            "add_columns" => [
                USERS_TABLE => [
                    "snp_fav_fid_exclude" => ["TEXT", ""],
                ],
            ],
        ];
    }

    public function revert_schema()
    {
        return [
            "drop_columns" => [
                USERS_TABLE => ["snp_fav_fid_exclude"],
            ],
        ];
    }

    public function update_data()
    {
        return [];
    }
}
