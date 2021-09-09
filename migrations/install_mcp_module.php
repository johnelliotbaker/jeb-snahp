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

class install_mcp_module extends \phpbb\db\migration\migration
{
    // public function effectively_installed()
    // {
    //     $sql = 'SELECT module_id
    //         FROM ' . $this->table_prefix . "modules
    //         WHERE module_class = 'mcp'
    //             AND module_langname = 'MCP_SNP_TITLE'";
    //     $result = $this->db->sql_query($sql);
    //     $module_id = $this->db->sql_fetchfield('module_id');
    //     $this->db->sql_freeresult($result);
    //     return $module_id !== false;
    // }

    public static function depends_on()
    {
        return ['\phpbb\db\migration\data\v31x\v314'];
    }

    public function update_data()
    {
        return [];
        // return array(
        //     array('module.add', array(
        //         'mcp',
        //         0,
        //         'MCP_SNP_TITLE'
        //     )),
        //     array('module.add', array(
        //         'mcp',
        //         'MCP_SNP_TITLE',
        //         array(
        //             'module_basename'	=> '\jeb\snahp\mcp\main_module',
        //             'modes'				=> array('front'),
        //         ),
        //     )),
        // );
    }
}
