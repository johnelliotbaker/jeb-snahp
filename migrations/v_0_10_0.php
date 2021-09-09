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

class v_0_10_0 extends \phpbb\db\migration\migration
{
    public static function depends_on()
    {
        return ['\jeb\snahp\migrations\v_0_9_0'];
    }

    public function update_data()
    {
        return [
            ["config.add", ["snp_don_b_show_navlink", 0]],
            [
                "config.add",
                ["snp_don_url", "https://forum.snahp.it/viewforum.php?f=7"],
            ],
            [
                "module.add",
                [
                    "acp",
                    "ACP_SNP_TITLE",
                    [
                        "module_basename" => "\jeb\snahp\acp\main_module",
                        "modes" => ["donation"],
                    ],
                ],
            ],
        ];
    }
}
