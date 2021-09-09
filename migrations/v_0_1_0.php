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

class v_0_1_0 extends \phpbb\db\migration\migration
{
    public function effectively_installed()
    {
        // return isset($this->config['b_snahp_install']);
        return false;
    }

    public static function depends_on()
    {
        return [
            "\jeb\snahp\migrations\install_user_schema",
            "\jeb\snahp\migrations\install_acp_module",
            "\jeb\snahp\migrations\install_mcp_module",
            "\jeb\snahp\migrations\install_ucp_module",
        ];
    }

    public function update_schema()
    {
        return [
            "add_columns" => [
                $this->table_prefix . "groups" => [
                    "snp_enable_signature" => ["BOOL", 0],
                    "snp_signature_rows" => ["UINT", 0],
                ],
            ],
        ];
    }

    public function revert_schema()
    {
        return [
            "drop_columns" => [
                $this->table_prefix . "groups" => [
                    "snp_enable_signature",
                    "snp_signature_rows",
                ],
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
                        "modes" => ["signature"],
                    ],
                ],
            ],
        ];
    }
}
