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

class v_0_8_0 extends \phpbb\db\migration\migration
{
    public function effectively_installed()
    {
        return false;
    }

    static public function depends_on()
    {
        return array(
            '\jeb\snahp\migrations\v_0_7_0',
        );
    }

    public function update_schema()
    {
        $time = time();
        $thirtydays = 60*60*24*30;
        $reset_time = $time + $thirtydays;
		return array(
			'add_tables'		=> array(
				$this->table_prefix . 'snahp_request'	=> array(
					'COLUMNS'       => array(
						'id'     => array('UINT', null, 'auto_increment'),
						'tid'    => array('INT:10', null),
						'pid'    => array('INT:10', null),
                        'fid'    => array('UINT', null),
                        'requester_uid'      => array('INT:10', null),
                        'requester_username' => array('VCHAR', ''),
                        'requester_colour'   => array('VCHAR:6', ''),
                        'fulfiller_uid'      => array('INT:10', null),
                        'fulfiller_username' => array('VCHAR', ''),
                        'fulfiller_colour'   => array('VCHAR:6', ''),
                        'created_time'       => array('INT:11', null),
                        'commit_time'        => array('INT:11', null),
                        'fulfilled_time'     => array('INT:11', null),
                        'solved_time'        => array('INT:11', null),
                        'status'             => array('TINT:2', 1),
                    ),
					'PRIMARY_KEY'   => 'id',
				),
				$this->table_prefix . 'snahp_request_users'	=> array(
					'COLUMNS'       => array(
						'id'       => array('UINT', null, 'auto_increment'),
                        'user_id'  => array('INT:10', null),
                        'n_base'   => array('UINT', 2),
                        'n_left'   => array('UINT', 2),
                        'n_offset' => array('INT:3', 1),
                        'start_time'      => array('INT:11', $time),
                        'cycle_deltatime' => array('INT:11', $thirtydays),
                        'reset_time'      => array('INT:11', $reset_time),
                        'b_unlimited'     => array('BOOL', 0),
                        'b_give'          => array('BOOL', 0),
                        'b_take'          => array('BOOL', 0),
                        'b_active'        => array('BOOL', 1),
                        'status'          => array('TINT:2', 1),
                    ),
					'PRIMARY_KEY'   => 'id',
                ),
            ),
            'add_unique_index' => array(
				$this->table_prefix . 'snahp_request'	=> array(
                    'tid' => array('tid'),
                ),
				$this->table_prefix . 'snahp_request_users'	=> array(
                    'tid' => array('user_id'),
                ),
            ),
		);
    }

    public function revert_schema()
    {
		return array(
			'drop_tables'		=> array(
				$this->table_prefix . 'snahp_request',
				$this->table_prefix . 'snahp_request_users',
			),
		);
    }

    public function update_data()
    {
        return array(
			array('config.add', array('snp_b_request', 1)),
			array('config.add', array('snp_request_fid', '1,2,3,4,5')),
            array('module.add', array(
                'acp',
                'ACP_SNP_TITLE',
                array(
                    'module_basename'	=> '\jeb\snahp\acp\main_module',
                    'modes'				=> array('request'),
                ),
            )),
        );
    }
}
