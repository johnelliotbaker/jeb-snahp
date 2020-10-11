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
        $Entry
    ) {
        $this->template = $template;
        $this->sauth = $sauth;
        $this->Entry = $Entry;
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
        ];
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
