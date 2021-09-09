<?php
/**
 * Create UCP Custom tab
 * */
namespace jeb\snahp\migrations;

class v_0_29_0 extends \phpbb\db\migration\migration
{
    public function effectively_installed()
    {
        return false;
    }

    public static function depends_on()
    {
        return ['\jeb\snahp\migrations\v_0_28_2'];
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
            ['config.add', ['snp_ucp_custom_b_master', 1]],
            ['module.add', [
                'ucp',
                'UCP_SNP_TITLE', [
                    'module_basename' => '\jeb\snahp\ucp\main_module',
                    'modes'           => [ 'custom', ],
                ],
            ]],
        ];
    }
}
