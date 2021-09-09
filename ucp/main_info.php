<?php
/**
 *
 * snahp. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2018
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace jeb\snahp\ucp;

/**
 * snahp UCP module info.
 */
class main_info
{
    public function module()
    {
        return [
            "filename" => "\jeb\snahp\ucp\main_module",
            "title" => "UCP_SNP_TITLE",
            "modes" => [
                "visibility" => [
                    "title" => "UCP_SNP_VIS",
                    "auth" => "ext_jeb/snahp",
                    "cat" => ["UCP_SNP_VIS_TITLE"],
                ],
                "invite" => [
                    "title" => "UCP_SNP_INVITE",
                    "auth" => "ext_jeb/snahp",
                    "cat" => ["UCP_SNP_INVITE_TITLE"],
                ],
                "custom" => [
                    "title" => "UCP_SNP_CUSTOM",
                    "auth" => "ext_jeb/snahp",
                    "cat" => ["UCP_SNP_CUSTOM_TITLE"],
                ],
                "block" => [
                    "title" => "UCP_SNP_BLOCK",
                    "auth" => "ext_jeb/snahp",
                    "cat" => ["UCP_SNP_BLOCK_TITLE"],
                ],
            ],
        ];
    }
}
