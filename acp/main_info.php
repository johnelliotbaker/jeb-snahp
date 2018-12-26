<?php
/**
 *
 * snahp. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2018
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace jeb\snahp\acp;

/**
 * snahp ACP module info.
 */
class main_info
{
	public function module()
	{
		return array(
			'filename'	=> '\jeb\snahp\acp\main_module',
			'title'		=> 'ACP_SNP_TITLE',
			'modes'		=> array(
				'signature'	=> array(
					'title'	=> 'ACP_SNP_SIGNATURE',
					'auth'	=> 'ext_jeb/snahp && acl_a_board',
					'cat'	=> array('ACP_SNP_TITLE')
				),
				'settings'	=> array(
					'title'	=> 'ACP_SNP_SETTINGS',
					'auth'	=> 'ext_jeb/snahp && acl_a_board',
					'cat'	=> array('ACP_SNP_TITLE')
				),
				'scripts'	=> array(
					'title'	=> 'ACP_SNP_SCRIPTS',
					'auth'	=> 'ext_jeb/snahp && acl_a_board',
					'cat'	=> array('ACP_SNP_TITLE')
				),
			),
		);
	}
}
