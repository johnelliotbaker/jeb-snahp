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

class v_0_2_0 extends \phpbb\db\migration\migration
{
    public function effectively_installed()
    {
        // return isset($this->config['b_snahp_install']);
        return false;
    }

    public static function depends_on()
    {
        return ['\jeb\snahp\migrations\v_0_1_0'];
    }

    public function update_schema()
    {
        return [
            "add_columns" => [
                $this->table_prefix . "groups" => [
                    "snp_imdb_enable" => ["BOOL", 0],
                ],
            ],
        ];
    }

    public function revert_schema()
    {
        return [
            "drop_columns" => [
                $this->table_prefix . "groups" => ["snp_imdb_enable"],
            ],
        ];
    }

    public function update_data()
    {
        return [
            [
                "module.add",
                [
                    "acp",
                    "ACP_SNP_TITLE",
                    [
                        "module_basename" => "\jeb\snahp\acp\main_module",
                        "modes" => ["imdb"],
                    ],
                ],
            ],
        ];
    }
}
