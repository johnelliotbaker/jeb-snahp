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
    'ACP_SNP_TITLE'         => 'Snahp',
    'ACP_SNP_SETTINGS'      => 'General Settings',
    'ACP_SNP_SCRIPTS'       => 'Scripts',
    'ACP_SNP_SIGNATURE'     => 'Signature',
	'ACP_SNP_SETTING_SAVED' => 'Settings have been saved successfully!',
));
