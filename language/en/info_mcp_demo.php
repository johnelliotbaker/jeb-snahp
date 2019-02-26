<?php
/**
 *
 * snahp. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2018
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

if (!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || !is_array($lang))
{
	$lang = array();
}

$lang = array_merge($lang, array(
	'MCP_SNP'         => 'Snahp',
	'MCP_SNP_TITLE'   => 'Snahp',
	'MCP_SNP_REQUEST' => 'List Requests',
	'MCP_SNP_DIBS'    => 'List Undibs',
	'MCP_SNP_BAN'     => 'Manage Users',
	'MCP_SNP_TOPIC_BUMP' => 'List Bumps',
));
