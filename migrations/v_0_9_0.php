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

class v_0_9_0 extends \phpbb\db\migration\migration
{
    public static function depends_on()
    {
        return array('\jeb\snahp\migrations\v_0_8_0');
    }

    public function update_data()
    {
        return array(
            array('module.add', array(
                'mcp',
                0,
                'MCP_SNP_TITLE'
            )),
            array('module.add', array(
                'mcp',
                'MCP_SNP_TITLE',
                array(
                    'module_basename'	=> '\jeb\snahp\mcp\main_module',
                    'modes'				=> array(
                        'request', 'dibs', 'ban',
                    ),
                ),
            )),
        );
    }
}
