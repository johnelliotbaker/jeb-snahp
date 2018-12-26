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

class install_acp_module extends \phpbb\db\migration\migration
{
    public function effectively_installed()
    {
        // return isset($this->config['b_snahp_install']);
        return false;
    }

    static public function depends_on()
    {
        return array('\phpbb\db\migration\data\v31x\v314');
    }

    public function update_data()
    {
        return array(
            array('config.add', array('b_snahp_install', 0)),
            array('module.add', array(
                'acp',
                'ACP_CAT_DOT_MODS',
                'ACP_SNP_TITLE'
            )),
            array('module.add', array(
                'acp',
                'ACP_SNP_TITLE',
                array(
                    'module_basename'	=> '\jeb\snahp\acp\main_module',
                    'modes'				=> array('settings', 'scripts'),
                ),
            )),
        );
    }
}
