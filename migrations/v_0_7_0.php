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

class v_0_7_0 extends \phpbb\db\migration\migration
{
    public function effectively_installed()
    {
        return false;
    }

    public static function depends_on()
    {
        return ['\jeb\snahp\migrations\v_0_6_0'];
    }

    public function update_schema()
    {
        return [
            "add_columns" => [
                $this->table_prefix . "users" => [
                    "snp_enable_at_notify" => ["UINT", 1],
                ],
            ],
        ];
    }

    public function revert_schema()
    {
        return [
            "drop_columns" => [
                $this->table_prefix . "users" => ["snp_enable_at_notify"],
            ],
        ];
    }

    public function update_data()
    {
        return [
            ["config.add", ["snp_b_snahp_notify", 1]],
            ["config.add", ["snp_b_notify_on_poke", 1]],
            ["config.add", ["snp_b_enable_basic_notification", 1]],
            [
                "module.add",
                [
                    "acp",
                    "ACP_SNP_TITLE",
                    [
                        "module_basename" => "\jeb\snahp\acp\main_module",
                        "modes" => ["notification"],
                    ],
                ],
            ],
        ];
    }
}
