<?php

/**
 *
 * snahp. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2018
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace jeb\snahp\event;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use jeb\snahp\core\logger\logger;

class foe_listener implements EventSubscriberInterface
{
    protected $db;
    protected $config;
    protected $user;
    protected $template;
    protected $container;
    protected $tbl;
    protected $sauth;
    protected $foe_helper;
    public function __construct(
        $db, $config, $user, $template, $container,
        $tbl,
        $sauth, $foe_helper
    )/*{{{*/
    {
        $this->db = $db;
        $this->config = $config;
        $this->user = $user;
        $this->template = $template;
        $this->container = $container;
        $this->tbl = $tbl;
        $this->sauth = $sauth;
        $this->foe_helper = $foe_helper;
        $this->user_id = $this->user->data['user_id'];
        $this->time = 0;
    }/*}}}*/

    static public function getSubscribedEvents()/*{{{*/
    {
        return [
            'core.viewtopic_modify_post_row' => [
                ['hide_from_blocked_user_on_viewtopic', 1],
            ],
            'core.modify_posting_auth' => [
                ['hide_from_blocked_user_on_reply', 0],
            ],
            'core.ucp_pm_compose_modify_data' => [
                ['hide_from_blocked_user_on_pm', 0]
            ],
            'core.submit_pm_before' => [
                ['disable_blocked_user_on_pm_submit', 0]
            ],
        ];
    }/*}}}*/

    public function disable_blocked_user_on_pm_submit($event)/*{{{*/
    {
        if (!$this->config['snp_foe_b_master']) return false;
        $data = $event['data'];
        $event['data'] = $data;
        $a_to_user_id = $data['address_list']['u'];
        $blocked_id = $this->user_id;
        foreach($a_to_user_id as $blocker_id=>$v)
        {
            if ($this->foe_helper->is_blocked_with_blocker_id($blocked_id, $blocker_id))
            {
                trigger_error('The recipient has chosen not to receive private messages from you. Error Code: 3a3abda323');
            }
        }
    }/*}}}*/

    public function hide_from_blocked_user_on_pm($event)/*{{{*/
    {
        if (!$this->config['snp_foe_b_master']) return false;
        $action = $event['action'];
        if ($action!='quotepost') return false;
        $post_id = (int) $event['msg_id'];
        $blocked_id = $this->user_id;
        if ($this->foe_helper->is_blocked_with_post_id($blocked_id, $post_id))
        {
            trigger_error('The recipient has chosen not to receive private messages from you. Error Code: 77d2a85f1e');
        }
    }/*}}}*/

    public function hide_from_blocked_user_on_reply($event)/*{{{*/
    {
        if (!$this->config['snp_foe_b_master']) return false;
        $a_reply_mode = ['reply', 'quote', 'edit'];
        if (!in_array($event['mode'], $a_reply_mode)) return false;
        $post_data = $event['post_data'];
        $blocker_id = $post_data['topic_poster'];
        $blocked_id = $this->user_id;
        if ($this->foe_helper->cannot_reply($blocked_id, $blocker_id))
        {
            trigger_error('You have been blocked by the topic starter. Error Code: cf82bd2706');
        }
    }/*}}}*/

    public function hide_from_blocked_user_on_viewtopic($event)/*{{{*/
    {
        if (!$this->config['snp_foe_b_master']) return false;
        $i_row = $event['current_row_number'];
        if ($i_row > 0) return false;
        $def_block = $this->container->getParameter('jeb.snahp.foe_blocker')['status']['block'];
        $topic_data = $event['topic_data'];
        $blocked_id = $this->user_id;
        $blocker_id = $topic_data['topic_poster'];
        if ($row = $this->foe_helper->select_blocked_data($blocked_id, $blocker_id))
        {
            if (!$row['allow_viewtopic'])
            {
                $row['created_time_local'] = $this->user->format_date($row['created_time']);
                $post_row = $event['post_row'];
                $post_row['MESSAGE'] = '';
                $event['post_row'] = $post_row;
                $this->template->assign_vars([
                    'S_FOE_BLOCK_NOTICE' => 'You have been blocked by the author and cannot view this post.',
                    'B_FOE_BLOCK_NOTICE' => true,
                    'FOE_BLOCK_DATA' => $row,
                ]);
            }
        }
    }/*}}}*/

}
