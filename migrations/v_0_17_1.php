<?php

namespace jeb\snahp\migrations;

class v_0_17_1 extends \phpbb\db\migration\migration
{
    public function effectively_installed()
    { return false; }

    static public function depends_on()
    { return ['\jeb\snahp\migrations\v_0_17_0']; }

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
            ['module.add', [
                'mcp',
                'MCP_SNP_TITLE', [
                    'module_basename'	=> '\jeb\snahp\mcp\main_module',
                    'modes'				=> [ 'topic_bump', ],
                ],
            ]],
        ];
    }

}
