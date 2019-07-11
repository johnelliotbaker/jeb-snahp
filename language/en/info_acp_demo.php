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
    'ACP_SNP_IMDB'          => 'Post Generators',
    'ACP_SNP_NOTIFICATION'  => 'Notification',
    'ACP_SNP_REQUEST'       => 'Request',
    'ACP_SNP_SIGNATURE'     => 'Signature',
    'ACP_SNP_DONATION'      => 'Donation',
    'ACP_SNP_BUMP_TOPIC'    => 'Bump Topic',
    'ACP_SNP_GROUP_BASED_SEARCH'    => 'Group Based Search',
    'ACP_SNP_ANALYTICS'     => 'Analytics',
    'ACP_SNP_EMOTES'        => 'Emoticons',
    'ACP_SNP_INVITE'        => 'Invite',
	'ACP_SNP_SETTING_SAVED' => 'Settings have been saved successfully!',
));
