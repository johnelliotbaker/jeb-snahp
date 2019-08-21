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
	'UCP_SNP'            => 'Settings',
	'UCP_SNP_TITLE'      => 'Snahp',
    'UCP_SNP_VIS'        => 'Visibility',
    'UCP_SNP_VIS_TITLE'  => 'Visibility',
    'UCP_SNP_INVITE'        => 'Invite',
    'UCP_SNP_INVITE_TITLE'  => 'Invite',
    'UCP_SNP_CUSTOM'        => 'Customize',
    'UCP_SNP_CUSTOM_TITLE'  => 'Customize',
    'UCP_SNP_SORT'       => 'Sorting',
    'UCP_SNP_SORT_TITLE' => 'Sorting',
    'UCP_SNP_SAVED'      => 'Settings have been saved successfully.'
));
