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

class v_0_7_0 extends \phpbb\db\migration\migration
{
    public function effectively_installed()
    {
        return false;
    }

    static public function depends_on()
    {
        return array(
            '\jeb\snahp\migrations\v_0_6_0',
        );
    }

    public function update_schema()
    {
		return array(
			'add_columns'	=> array(
				$this->table_prefix . 'users'  => array(
					'snp_enable_at_notify' => array('UINT', 1),
				),
			),
		);
    }

    public function revert_schema()
    {
		return array(
			'drop_columns'	=> array(
				$this->table_prefix . 'users'  => array(
					'snp_enable_at_notify',
				),
			),
		);
    }

    public function update_data()
    {
        return array(
			array('config.add', array('snp_b_snahp_notify', 1)),
			array('config.add', array('snp_b_notify_on_poke', 1)),
			array('config.add', array('snp_b_enable_basic_notification', 1)),
            array('module.add', array(
                'acp',
                'ACP_SNP_TITLE',
                array(
                    'module_basename'	=> '\jeb\snahp\acp\main_module',
                    'modes'				=> array('notification'),
                ),
            )),
        );
    }
}
