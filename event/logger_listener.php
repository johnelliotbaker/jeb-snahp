<?php

/*{{{*/
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

class logger_listener implements EventSubscriberInterface
{
    protected $logger;
    protected $sauth;
    public function __construct(
        $logger, $sauth
    )/*{{{*/
    {
        $this->logger = $logger;
        $this->sauth = $sauth;
        $this->b_ban = !$this->sauth->is_only_dev();
    }/*}}}*/

    static public function getSubscribedEvents()/*{{{*/
    {
        return [
            'core.viewtopic_before_f_read_check' => [
                ['log_viewtopic', 0],
            ],
            'core.viewtopic_modify_page_title' => [
                ['log_viewtopic', 0],
            ],
            'core.viewtopic_post_row_after' => [
                ['log_viewtopic', 0],
            ],
            'core.viewtopic_modify_post_row' => [
                ['log_viewtopic', 0],
            ],
            'core.viewtopic_modify_post_action_conditions' => [
                ['log_viewtopic', 0],
            ],
            'core.viewtopic_modify_post_data' => [
                ['log_viewtopic', 0],
            ],
            'core.viewtopic_cache_user_data' => [
                ['log_viewtopic', 0],
            ],
            'core.viewtopic_cache_guest_data' => [
                ['log_viewtopic', 0],
            ],
            'core.viewtopic_post_rowset_data' => [
                ['log_viewtopic', 0],
            ],
            'core.viewtopic_get_post_data' => [
                ['log_viewtopic', 0],
            ],
            'core.viewtopic_modify_poll_template_data' => [
                ['log_viewtopic', 0],
            ],
            'core.viewtopic_modify_poll_data' => [
                ['log_viewtopic', 0],
            ],
            'core.viewtopic_assign_template_vars_before' => [
                ['log_viewtopic', 0],
            ],
            'core.viewtopic_add_quickmod_option_before' => [
                ['log_viewtopic', 0],
            ],
            'core.viewtopic_highlight_modify' => [
                ['log_viewtopic', 0],
            ],

            'core.modify_posting_parameters' => [
                ['log_posting', 0],
            ],
            'core.posting_modify_row_data' => [
                ['log_posting', 0],
            ],
            'core.modify_posting_auth' => [
                ['log_posting', 0],
            ],
            'core.posting_modify_cannot_edit_conditions' => [
                ['log_posting', 0],
            ],
            'core.posting_modify_default_variables' => [
                ['log_posting', 0],
            ],
            'core.posting_modify_message_text' => [
                ['log_posting', 0],
            ],
            'core.posting_modify_submission_errors' => [
                ['log_posting', 0],
            ],
            'core.posting_modify_submit_post_before' => [
                ['log_posting', 0],
            ],
            'core.posting_modify_submit_post_after' => [
                ['log_posting', 0],
            ],
            'core.posting_modify_quote_attributes' => [
                ['log_posting', 0],
            ],
            'core.posting_modify_post_subject' => [
                ['log_posting', 0],
            ],
            'core.posting_modify_template_vars' => [
                ['log_posting', 0],
            ],
            'jeb.snahp.notify_on_poke_before' => [
                ['log_posting', 0],
            ],
            'jeb.snahp.notify_on_poke_after' => [
                ['log_posting', 0],
            ],
            'jeb.snahp.notify_on_poke_before_notification' => [
                ['log_posting', 0],
            ],
            'jeb.snahp.notify_on_poke_after_notification' => [
                ['log_posting', 0],
            ],
        ];
    }/*}}}*/

    public function log_viewtopic($event, $event_name)/*{{{*/
    {
        if ($this->b_ban) { return false; }
        $event_name = (string) $event_name;
        $time = (string)(microtime(true) * 1000000);
        $data = [
            'type' => 'viewtopic',
            'name' => $event_name,
            'created_time' => $time,
        ];
        $this->logger->log($data);
    }/*}}}*/

    public function log_posting($event, $event_name)/*{{{*/
    {
        if ($this->b_ban) { return false; }
        $event_name = (string) $event_name;
        $time = (string)(microtime(true) * 1000000);
        $data = [
            'type' => 'posting',
            'name' => $event_name,
            'created_time' => $time,
        ];
        $this->logger->log($data);
    }/*}}}*/

}
