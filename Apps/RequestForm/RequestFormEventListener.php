<?php
namespace jeb\snahp\Apps\RequestForm;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class RequestFormEventListener implements EventSubscriberInterface
{
    protected $helper;
    protected $data;
    protected $form;

    public function __construct(/*{{{*/
        $helper
    ) {
        $this->helper = $helper;
        $this->data = [];
        $this->bbcodeAdded = false;
    }/*}}}*/

    public static function getSubscribedEvents()/*{{{*/
    {
        return [
            'core.modify_format_display_text_after' => [
                ['embedRequestTemplateInPreview', 1],
            ],
            'core.viewtopic_assign_template_vars_before' => [
                ['setTopicVars', 1],
            ],
            'core.modify_posting_parameters' => [
                ['setPostingVars', 1],
            ],
            'core.posting_modify_template_vars' => [
                ['embedCustomForm', 1],
            ],
            'core.viewtopic_modify_post_row' => [
                ['embedRequestTemplate', 1],
            ],
            'core.posting_modify_message_text' => [
                // Using core.posting_modify_message_text instead of core.posting_modify_submission_errors
                // to resolve conflict from request_listener:compose_request_form
                ['handleFormErrors', 2],
                ['prependRequestBBCodeToPostData', 1],
            ],
            'core.posting_modify_submit_post_before' => [
                ['disableMagicUrl', 1],
                ['tagTopicTitle', 1],
            ],
        ];
    }/*}}}*/

    public function tagTopicTitle($event)/*{{{*/
    {
        if ($this->form && $event['mode'] === 'post') {
            $postData = $event['post_data'];
            $title = &$postData['post_subject'];
            $title = $this->helper->tagTopicTitle($title, $this->form);
            $event['post_data'] = $postData;
        }
    }/*}}}*/

    public function disableMagicUrl($event)/*{{{*/
    {
        $postData = $event['post_data'];
        $postData['enable_urls'] = 0;
        $event['post_data'] = $postData;
    }/*}}}*/

    public function setPostingVars($event)/*{{{*/
    {
        $forumId = $event['forum_id'];
        $this->data['posting']['isInRequestForum'] = forumIsRequest($forumId);
        $this->data['posting']['requestForumName'] = getRequestForumName($forumId);
    }/*}}}*/

    public function setTopicVars($event)/*{{{*/
    {
        $forumId = $event['forum_id'];
        $this->data['topic']['isInRequestForum'] = forumIsRequest($forumId);
        $this->data['topic']['requestForumName'] = getRequestForumName($forumId);
    }/*}}}*/

    public function embedRequestTemplateInPreview($event)/*{{{*/
    {
        $preview = (isset($_POST['preview'])) ? true : false;
        $text = $event['text'];
        if ($preview) {
            include_once '/var/www/forum/ext/jeb/snahp/Apps/RequestForm/FormTemplateMakers/FormTemplateMaker.php';
            $text = preg_replace_callback(
                '#<request>(.*?)<\/request>#s',
                function ($matches) {
                    $data = json_decode(
                        preg_replace("/<br>/", "", $matches[1]),
                        true
                    );
                    if ($data) {
                        $formMaker = new \jeb\snahp\Apps\RequestForm\FormTemplateMakers\FormTemplateMaker();
                        $text = $formMaker->makeRequestTemplateHTML($data);
                    }
                    return $text;
                },
                $text
            );
            $event["text"] = $text;
        }
    }/*}}}*/

    public function embedRequestTemplate($event)/*{{{*/
    {
        $isFirstPage = $event["current_row_number"] === 0;
        $isOpeningPost = $isFirstPage && (int) $event['start'] === 0;
        $isRequest = $this->data['topic']['isInRequestForum'];
        if ($isOpeningPost && $isRequest) {
            include_once '/var/www/forum/ext/jeb/snahp/Apps/RequestForm/FormTemplateMakers/FormTemplateMaker.php';
            $postRow = $event["post_row"];
            $postRow['MESSAGE'] = preg_replace_callback(
                '#<request>(.*?)<\/request>#s',
                function ($matches) {
                    $data = json_decode(
                        preg_replace("/<br>/", "", $matches[1]),
                        true
                    );
                    if ($data) {
                        $formMaker = new \jeb\snahp\Apps\RequestForm\FormTemplateMakers\FormTemplateMaker();
                        return $formMaker->makeRequestTemplateHTML($data);
                    }
                },
                $postRow['MESSAGE']
            );
            $event["post_row"] = $postRow;
        }
    }/*}}}*/

    public function embedCustomForm($event)/*{{{*/
    {
        $isPost = $event['mode'] === 'post';
        $isRequest = $this->data['posting']['isInRequestForum'];
        if ($isPost && $isRequest) {
            $this->helper->embedCustomForm($event['forum_id']);
        }
    }/*}}}*/

    public function handleFormErrors($event)/*{{{*/
    {
        if (!$this->data['posting']['isInRequestForum']) {
            return;
        }
        $forumId = (int) $event['forum_id'];
        if ($event['mode'] === 'post') {
            $form = $this->helper->createFormByRequestForumId($forumId);
            $this->helper->isValid($form);
            $errors = $this->helper->collectErrors($form);
            if ($errors) {
                $error = $event['error'];
                $error[] = '';
                $event['error'] = $error;
            } else {
                $this->form = $form;
            }
        }
    }/*}}}*/

    public function prependRequestBBCodeToPostData($event)/*{{{*/
    {
        if ($event['mode'] !== 'post') {
            return;
        }
        $postData = $event['post_data'];
        $messageParser = $event['message_parser'];
        $message = &$messageParser->message;
        $extra = [
            'title' => $postData['post_subject'],
        ];
        $bbcode = $this->helper->makeRequestBBCode($this->form, $extra);
        $message = $this->helper->removeRequestBBCode($message);
        $message = $bbcode . PHP_EOL . $message;
        $this->bbcodeAdded = true;
    }/*}}}*/
}
