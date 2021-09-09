<?php
/**
 *
 * snahp. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2018
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace jeb\snahp\acp;

/**
 * snahp ACP module info.
 */
class main_info
{
    public function module()
    {
        return [
            "filename" => "\jeb\snahp\acp\main_module",
            "title" => "ACP_SNP_TITLE",
            "modes" => [
                "thanks" => [
                    "title" => "ACP_SNP_THANKS",
                    "auth" => "ext_jeb/snahp && acl_a_board",
                    "cat" => ["ACP_SNP_TITLE"],
                ],
                "emotes" => [
                    "title" => "ACP_SNP_EMOTES",
                    "auth" => "ext_jeb/snahp && acl_a_board",
                    "cat" => ["ACP_SNP_TITLE"],
                ],
                "donation" => [
                    "title" => "ACP_SNP_DONATION",
                    "auth" => "ext_jeb/snahp && acl_a_board",
                    "cat" => ["ACP_SNP_TITLE"],
                ],
                "signature" => [
                    "title" => "ACP_SNP_SIGNATURE",
                    "auth" => "ext_jeb/snahp && acl_a_board",
                    "cat" => ["ACP_SNP_TITLE"],
                ],
                "settings" => [
                    "title" => "ACP_SNP_SETTINGS",
                    "auth" => "ext_jeb/snahp && acl_a_board",
                    "cat" => ["ACP_SNP_TITLE"],
                ],
                "scripts" => [
                    "title" => "ACP_SNP_SCRIPTS",
                    "auth" => "ext_jeb/snahp && acl_a_board",
                    "cat" => ["ACP_SNP_TITLE"],
                ],
                "imdb" => [
                    "title" => "ACP_SNP_IMDB",
                    "auth" => "ext_jeb/snahp && acl_a_board",
                    "cat" => ["ACP_SNP_TITLE"],
                ],
                "notification" => [
                    "title" => "ACP_SNP_NOTIFICATION",
                    "auth" => "ext_jeb/snahp && acl_a_board",
                    "cat" => ["ACP_SNP_TITLE"],
                ],
                "request" => [
                    "title" => "ACP_SNP_REQUEST",
                    "auth" => "ext_jeb/snahp && acl_a_board",
                    "cat" => ["ACP_SNP_TITLE"],
                ],
                "bump_topic" => [
                    "title" => "ACP_SNP_BUMP_TOPIC",
                    "auth" => "ext_jeb/snahp && acl_a_board",
                    "cat" => ["ACP_SNP_TITLE"],
                ],
                "group_based_search" => [
                    "title" => "ACP_SNP_GROUP_BASED_SEARCH",
                    "auth" => "ext_jeb/snahp && acl_a_board",
                    "cat" => ["ACP_SNP_TITLE"],
                ],
                "analytics" => [
                    "title" => "ACP_SNP_ANALYTICS",
                    "auth" => "ext_jeb/snahp && acl_a_board",
                    "cat" => ["ACP_SNP_TITLE"],
                ],
                "invite" => [
                    "title" => "ACP_SNP_INVITE",
                    "auth" => "ext_jeb/snahp && acl_a_board",
                    "cat" => ["ACP_SNP_TITLE"],
                ],
            ],
        ];
    }
}
