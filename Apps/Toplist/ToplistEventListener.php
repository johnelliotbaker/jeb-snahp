<?php
namespace jeb\snahp\Apps\Toplist;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ToplistEventListener implements EventSubscriberInterface
{
    protected $template;
    protected $sauth;
    protected $helper;
    public function __construct(/*{{{*/
        $config,
        $template,
        $sauth,
        $helper
    ) {
        $this->config = $config;
        $this->template = $template;
        $this->sauth = $sauth;
        $this->helper = $helper;
        $this->userId = $this->sauth->userId;
    }/*}}}*/

    public static function getSubscribedEvents()/*{{{*/
    {
        return [
            'core.index_modify_page_title' => [
                ['embedToplist', 1],
            ],
        ];
    }/*}}}*/

    public function embedToplist($event)/*{{{*/
    {
        if ($this->userId == ANONYMOUS) {
            return false;
        }
        if (!$this->config['snp_thanks_b_enable']) {
            return false;
        }
        if (!$this->config['snp_thanks_b_toplist']) {
            return false;
        }
        $this->template->assign_var('B_SHOW_TOPLIST', true);
        $qs = $this->helper->getThanksToplist();
        foreach ($qs as $row) {
            $data = [
                'USERNAME'              => $row['username'],
                'USER_COLOUR'           => $row['user_colour'],
                'USER_ID'               => $row['user_id'],
                'COUNT' => $row['snp_thanks_n_received'],
            ];
            $this->template->assign_block_vars('A_THANKS_TOP_LIST', $data);
        }
        $qs = $this->helper->getRequestSolvedToplist();
        foreach ($qs as $row) {
            $data = [
                'USERNAME'              => $row['username'],
                'USER_COLOUR'           => $row['user_colour'],
                'USER_ID'               => $row['user_id'],
                'COUNT' => $row['snp_req_n_solve'],
            ];
            $this->template->assign_block_vars('A_REQUEST_SOLVED_TOP_LIST', $data);
        }
        $qs = $this->helper->getReputationToplist();
        foreach ($qs as $row) {
            $data = [
                'USERNAME'              => $row['username'],
                'USER_COLOUR'           => $row['user_colour'],
                'USER_ID'               => $row['user_id'],
                'COUNT' => $row['snp_rep_n_received'],
            ];
            $this->template->assign_block_vars('A_REPUTATION_TOP_LIST', $data);
        }
    }/*}}}*/
}
