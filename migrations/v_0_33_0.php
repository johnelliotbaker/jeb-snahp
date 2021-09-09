<?php

// Add thanks module to ACP

namespace jeb\snahp\migrations;

class v_0_33_0 extends \phpbb\db\migration\migration
{
    public function effectively_installed()
    {
        return false;
    }

    public static function depends_on()
    {
        return ['\jeb\snahp\migrations\v_0_32_0'];
    }

    public function update_schema()
    {
        return [];
    }

    public function revert_schema()
    {
        return [];
    }

    public function update_data()
    {
        return [
            ["config.add", ["snp_thanks_b_limit_cycle", 1]],
            ["config.add", ["snp_thanks_cycle_duration", 86400]],
            [
                "module.add",
                [
                    "acp",
                    "ACP_SNP_TITLE",
                    [
                        "module_basename" => "\jeb\snahp\acp\main_module",
                        "modes" => ["thanks"],
                    ],
                ],
            ],
        ];
    }
}
