<?php
namespace jeb\snahp\Apps\Deadlinks;

require_once '/var/www/forum/ext/jeb/snahp/Apps/DeadLinks/Models/Entry.php';

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class DeadlinksEventListener implements EventSubscriberInterface
{
    protected $template;
    protected $sauth;
    protected $Entry;
    public function __construct(/*{{{*/
        $template,
        $sauth,
        $Entry,
        $helper
    ) {
        $this->template = $template;
        $this->sauth = $sauth;
        $this->Entry = $Entry;
        $this->helper = $helper;
    }/*}}}*/

    public static function getSubscribedEvents()/*{{{*/
    {
        return [
            'core.user_setup_after' => [
                ['showDeadlinks', 1],
            ],
            'core.viewtopic_assign_template_vars_before'  => [
                ['setDeadlinkTagInTitle', 0],
            ],
            'core.search_modify_param_before'  => [
                ['setKeywordSearchFlag', 0],
            ],
            'core.search_modify_url_parameters'  => [
                ['hideDeadlinksInSearch', 0],
            ],
            'core.viewforum_modify_topics_data'  => [
                ['setDeadlinkTagInViewForum', 10],
            ],
            'core.search_modify_tpl_ary'  => [
                ['setDeadlinksTagInSearch', 10],
            ],
        ];
    }/*}}}*/

    public function setDeadlinksTagInSearch($event)/*{{{*/
    {
        $row = $event['row'];
        $dead = $row['snp_ded_b_dead'];
        if ($dead) {
            $tpl_ary = $event['tpl_ary'];
            $pre = '<span style="color:#900;opacity:0.7;">[Deadlinks] ';
            $post = '</span>';
            $tpl_ary['TOPIC_TITLE'] = $pre . $tpl_ary['TOPIC_TITLE'] . $post;
            $event['tpl_ary'] = $tpl_ary;
        }
    }/*}}}*/

    public function setDeadlinkTagInViewForum($event)/*{{{*/
    {
        $rowset = $event['rowset'];
        foreach ($rowset as $k => &$row) {
            if ($row['snp_ded_b_dead'] == 1) {
                $row['topic_title'] = '[Deadlinks]' . $row['topic_title'];
            }
        }
        $event['rowset'] = $rowset;
    }/*}}}*/

    public function setKeywordSearchFlag($event)/*{{{*/
    {
        // Filters out graveyard
        $event['ex_fid_ary'] = $this->helper->appendGraveyardToExFidAry($event['ex_fid_ary']);
        // setup for hideDeadlinksInSearch
        $this->searchKeywords = $event['keywords'];
    }/*}}}*/

    public function hideDeadlinksInSearch($event)/*{{{*/
    {
        // Search is used for other things like "your posts".
        // Should only hide deadlinks for keywords search
        if ($this->searchKeywords) {
            $sql_where = $event['sql_where'];
            if ($sql_where) {
                $sql_where .= ' AND t.snp_ded_b_dead<>1';
                $event['sql_where'] = $sql_where;
            }
        }
    }/*}}}*/

    public function showDeadlinks($event)/*{{{*/
    {
        // TODO: UNCOMMENT AFTER INIT
        $openReports = count($this->Entry->getOpenReports($this->sauth->userId));
        $this->template->assign_var('N_OPEN_REPORTS', $openReports);
        if ($this->sauth->is_dev()) {
            $openRequests = count($this->Entry->getOpenRequests());
            $this->template->assign_var('N_OPEN_REQUESTS', $openRequests);
        }
    }/*}}}*/

    public function setDeadlinkTagInTitle($event)/*{{{*/
    {
        $topic_data = $event['topic_data'];
        $isDeadlink = $topic_data['snp_ded_b_dead'];
        if ($isDeadlink) {
            $topic_data['topic_title'] = '[Deadlinks] ' . $topic_data['topic_title'];
        }
        $event['topic_data'] = $topic_data;
    }/*}}}*/
}
