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

class v_0_5_0 extends \phpbb\db\migration\migration
{
    public function effectively_installed()
    {
        return false;
    }

    static public function depends_on()
    {
        return array(
            '\jeb\snahp\migrations\v_0_4_0',
        );
    }

    public function update_schema()
    {

		return array(
			'add_tables'		=> array(
				$this->table_prefix . 'snahp_pg_fid'	=> array(
					'COLUMNS'       => array(
						'name'   => array('VCHAR', ""),
						'fid'   => array('TEXT', ""),
					),
					'PRIMARY_KEY'   => 'name',
				),
			),
        );
    }

    public function revert_schema()
    {
        return array(
            'drop_tables'	=> array(
                $this->table_prefix . 'snahp_pg_fid',
            ),
        );
    }

    public function update_data()
    {
        return array(
            array('custom', array(array($this, 'update_snahp_table'))),
        );
    }

	public function update_snahp_table()
	{
        $anime = [13, 16,];
        $listing = [4, 9, 10, 11, 12, 13, 14, 15, 24, 25, 26, 27, 29, 30, 31, 32, 33, 34, 35, 36, 37, 38, 39, 40, 41, 42, 43, 44, 45, 46, 47, 55, 56, 57, 58, 59, 60, 61, 62, 63, 64, 65, 66, 72, 73, 74, 75, 76, 77, 78, 79, 80, 81, 82,];
        $book = [15, 37, 38, 39, 43, 44, 59];

		$tbl = $this->table_prefix . 'snahp_pg_fid';
        $sql = 'INSERT INTO ' . $tbl . '(name, fid) VALUES ("anime", "' . implode(", ", $anime) . '"); ';
        $this->db->sql_query($sql);
        $sql = 'INSERT INTO ' . $tbl . '(name, fid) VALUES ("listing", "' . implode(", ", $listing) . '"); ';
        $this->db->sql_query($sql);
        $sql = 'INSERT INTO ' . $tbl . '(name, fid) VALUES ("book", "' . implode(", ", $book) . '"); ';
        $this->db->sql_query($sql);
	}
}
