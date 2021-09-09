<?php

namespace jeb\snahp\migrations;

class v_0_19_0 extends \phpbb\db\migration\migration
{
    public function effectively_installed()
    {
        return false;
    }

    public static function depends_on()
    {
        return ['\jeb\snahp\migrations\v_0_18_0'];
    }

    public function update_data()
    {
        return [
            ['config.add', ['snp_inv_b_master', 0]],
            ['config.add', ['snp_inv_b_mcp_invite', 1]],
            ['module.add', [
                'acp',
                'ACP_SNP_TITLE',
                [
                    'module_basename' => '\jeb\snahp\acp\main_module',
                    'modes'           => ['invite'],
                ],
            ]],
            ['module.add', [
                'mcp',
                'MCP_SNP_TITLE', [
                    'module_basename' => '\jeb\snahp\mcp\main_module',
                    'modes'           => [ 'invite', ],
                ],
            ]],
            ['module.add', [
                'ucp',
                'UCP_SNP_TITLE', [
                    'module_basename' => '\jeb\snahp\ucp\main_module',
                    'modes'           => [ 'invite', ],
                ],
            ]],
        ];
    }
}
