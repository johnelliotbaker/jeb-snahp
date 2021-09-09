<?php
/**
 *
 * snahp. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2018
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace jeb\snahp\migrations;

class v_0_12_1 extends \phpbb\db\migration\migration
{
    public function effectively_installed()
    {
        return false;
    }

    public static function depends_on()
    {
        return ['\jeb\snahp\migrations\v_0_12_0'];
    }

    public function update_schema()
    {
        return [
            "add_columns" => [
                $this->table_prefix . "snahp_request" => [
                    "b_graveyard" => ["BOOL", 0],
                ],
            ],
            "add_index" => [
                $this->table_prefix . "snahp_request" => [
                    "status" => ["status"],
                    "fid" => ["fid"],
                    "b_graveyard" => ["b_graveyard"],
                ],
            ],
        ];
    }

    public function revert_schema()
    {
        return [
            "drop_columns" => [
                $this->table_prefix . "snahp_request" => ["b_graveyard"],
            ],
        ];
    }
}
