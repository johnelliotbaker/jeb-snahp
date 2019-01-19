<?php
namespace jeb\snahp\mcp;
class main_info
{
	public function module()
	{
		return array(
			'filename'	=> '\jeb\snahp\mcp\main_module',
			'title'		=> 'MCP_SNP_TITLE',
			'modes'		=> array(
				'request'	=> array(
					'title'	=> 'MCP_SNP_REQUEST',
					'auth'	=> 'ext_jeb/snahp',
					'cat'	=> array('MCP_SNP_TITLE')
				),
				'dibs'	=> array(
					'title'	=> 'MCP_SNP_DIBS',
					'auth'	=> 'ext_jeb/snahp',
					'cat'	=> array('MCP_SNP_TITLE')
				),
				'ban'	=> array(
					'title'	=> 'MCP_SNP_BAN',
					'auth'	=> 'ext_jeb/snahp',
					'cat'	=> array('MCP_SNP_TITLE')
				),
			),
		);
	}
}
