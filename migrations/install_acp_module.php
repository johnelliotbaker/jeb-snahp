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

class install_acp_module extends \phpbb\db\migration\migration
{
    public function effectively_installed()
    {
        // return isset($this->config['b_snahp_install']);
        return false;
    }

    public static function depends_on()
    {
        return ['\phpbb\db\migration\data\v31x\v314'];
    }

    public function update_data()
    {
        return [
            ["config.add", ["b_snahp_install", 0]],
            ["module.add", ["acp", "ACP_CAT_DOT_MODS", "ACP_SNP_TITLE"]],
            [
                "module.add",
                [
                    "acp",
                    "ACP_SNP_TITLE",
                    [
                        "module_basename" => "\jeb\snahp\acp\main_module",
                        "modes" => ["settings", "scripts"],
                    ],
                ],
            ],
        ];
    }
}
