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

class v_0_11_0 extends \phpbb\db\migration\migration
{
    public function effectively_installed()
    {
        return false;
    }

    public static function depends_on()
    {
        return ['\jeb\snahp\migrations\v_0_10_0'];
    }

    public function update_schema()
    {
        return [
            "add_columns" => [
                $this->table_prefix . "groups" => [
                    "snp_gamespot_enable" => ["BOOL", 0],
                ],
            ],
        ];
    }

    public function revert_schema()
    {
        return [
            "drop_columns" => [
                $this->table_prefix . "groups" => ["snp_gamespot_enable"],
            ],
        ];
    }

    public function update_data()
    {
        $game = [10, 25, 41, 82];
        return [["config.add", ["snp_pg_fid_game", implode(",", $game)]]];
    }
}
