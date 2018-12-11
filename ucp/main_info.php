<?php
/**
 *
 * snahp. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2018
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace jeb\snahp\ucp;

/**
 * snahp UCP module info.
 */
class main_info
{
  function module()
  {
    return array(
      'filename'  => '\jeb\snahp\ucp\main_module',
      'title'   => 'UCP_SNP_TITLE',
      'modes'   => array(
        'visibility'  => array( 'title' => 'UCP_SNP_VIS', 'auth'  => 'ext_jeb/snahp', 'cat' => array('UCP_SNP_VIS_TITLE')),
      ),
    );
  }
}
