<?php

namespace jeb\snahp\migrations;

class v_0_18_0 extends \phpbb\db\migration\migration
{
    public function effectively_installed()
    {
        return false;
    }

    public static function depends_on()
    {
        return ['\jeb\snahp\migrations\v_0_17_1'];
    }

    public function update_schema()
    {
        return [
            'add_columns'	=> [
                $this->table_prefix . 'groups'  => [
                    'snp_ana_b_enable' => ['BOOL', 0],
                ],
            ],
        ];
    }

    public function revert_schema()
    {
        return [
            'drop_columns'	=> [
                $this->table_prefix . 'groups'  => [
                    'snp_ana_b_enable',
                ],
            ],
        ];
    }

    public function update_data()
    {
        return [
            ['config.add', ['snp_ana_b_master', 1]],
            ['config.add', ['snp_fid_requests', 5]],
            ['config.add', ['snp_zebra_b_master', true]],
            ['config.add', ['snp_zebra_b_topicview_block_foe', true]],
            ['config.add', ['snp_zebra_b_topicview_friend_only', true]],
            ['module.add', [
                'acp',
                'ACP_SNP_TITLE',
                [
                    'module_basename'	=> '\jeb\snahp\acp\main_module',
                    'modes'				=> ['analytics'],
                ],
            ]],
        ];
    }
}
