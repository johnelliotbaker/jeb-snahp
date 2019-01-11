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
    static public function depends_on()
    {
        return array(
            '\jeb\snahp\migrations\v_0_6_0',
        );
    }

    public function update_schema()
    {
		return array(
			'add_tables'		=> array(
				$this->table_prefix . 'snahp_dibs'	=> array(
					'COLUMNS'       => array(
						'id'  => array('UINT', null, 'auto_increment'),
						'tid' => array('INT:10'),
						'pid' => array('INT:10', null),
                        'fid' => array('UINT', null),
                        'requester_uid'      => array('INT:10', null),
                        'fulfiller_uid'      => array('INT:10', null),
                        'fulfiller_username' => array('VCHAR', ''),
                        'fulfiller_colour'   => array('VCHAR:6', ''),
                        'commit_time'        => array('INT:11', null),
                        'fulfilled_time'     => array('INT:11', null),
                        'confirmed_time'     => array('INT:11', null),
                    ),
					'PRIMARY_KEY'   => 'id',
				),
			),
            'add_unique_index' => array(
				$this->table_prefix . 'snahp_dibs'	=> array(
                    'tid' => array('tid'),
                )
            ),
		);
    }

    public function revert_schema()
    {
		return array(
			'drop_tables'		=> array(
				$this->table_prefix . 'snahp_dibs',
			),
		);
    }

    public function update_data()
    {
    }
}
