<?php
namespace jeb\snahp\Apps\PostingViolation;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PostingViolationEventListener implements EventSubscriberInterface
{
    protected $user;
    protected $config;
    protected $sauth;
    protected $helper;
    public function __construct(/*{{{*/
        $user,
        $config,
        $request,
        $template,
        $sauth,
        $helper
    ) {
        $this->user = $user;
        $this->config = $config;
        $this->request = $request;
        $this->template = $template;
        $this->sauth = $sauth;
        $this->helper = $helper;
        $this->user_id = $this->user->data['user_id'];
    }/*}}}*/

    public static function getSubscribedEvents()/*{{{*/
    {
        return [
            'core.posting_modify_submit_post_before' => [
                ['submitPostWithConfirmation', 1],
            ],
            'core.posting_modify_submit_post_after' => [
                ['incrementUserViolation', 1],
            ],
            'core.viewtopic_assign_template_vars_before' => [
                ['showViolationWarning', 1],
            ],
        ];
    }/*}}}*/

    public function showViolationWarning($event)/*{{{*/
    {
        $topicData = $event['topic_data'];
        if ($topicData['snp_violation']) {
            $this->template->assign_vars(
                [
                'TOPIC_IS_IN_POSTING_VIOLATION' => true,
                'POSTING_VIOLATION_REASON' => $topicData['snp_violation_reason'],
                ]
            );
        }

    }/*}}}*/

    public function incrementUserViolation($event)/*{{{*/
    {
        if ($this->sauth->is_dev()) {
            return;
        }
        $postData = $event['post_data'];
        $posterId = (int) $postData['poster_id'];
        if ($posterId && (int) $postData['snp_violation'] === 1) {
            $this->helper->incrementUserViolation($posterId);
        };
    }/*}}}*/

    public function submitPostWithConfirmation($event)
    {
        if ($this->sauth->is_dev()) {
            return;
        }
        $topicId = (int) $event['topic_id'];
        if (!$topicId) {
            return;
        }
        $topicData = $this->helper->getTopicData($topicId);
        if ($topicData['snp_violation']) {
            $this->helper->submitPostWithConfirmation($topicData['snp_violation_reason']);
        }
    }
}
