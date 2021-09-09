<?php

namespace jeb\snahp\migrations;

class v_0_28_0 extends \phpbb\db\migration\migration
{
    public function effectively_installed()
    {
        return false;
    }

    public static function depends_on()
    {
        return ['\jeb\snahp\migrations\v_0_27_3'];
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
            ['module.add', [
                'acp',
                'ACP_SNP_TITLE',
                [
                    'module_basename'	=> '\jeb\snahp\acp\main_module',
                    'modes'				=> ['emotes'],
                ]
            ]],
            ['config.add', ['snp_emo_b_master', 1]],
        ];
    }
}
