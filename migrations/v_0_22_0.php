<?php

namespace jeb\snahp\migrations;

class v_0_22_0 extends \phpbb\db\migration\migration
{
    public function effectively_installed()
    {
        return false;
    }

    public static function depends_on()
    {
        return ['\jeb\snahp\migrations\v_0_21_4'];
    }

    public function update_schema()
    {
    }

    public function revert_schema()
    {
    }

    public function update_data()
    {
        return [
            ['module.add', [
                'mcp',
                'MCP_SNP_TITLE',
                [
                    'module_basename' => '\jeb\snahp\mcp\main_module',
                    'modes'           => [ 'scripts', ],
                ],
            ]],

        ];
    }
}
