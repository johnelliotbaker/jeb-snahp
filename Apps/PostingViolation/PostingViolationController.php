<?php
namespace jeb\snahp\Apps\PostingViolation;

use \Symfony\Component\HttpFoundation\Response;
use \Symfony\Component\HttpFoundation\JsonResponse;

class PostingViolationController
{
    protected $config;
    protected $request;
    protected $template;
    protected $sauth;
    protected $helper;
    public function __construct(
        $config,
        $request,
        $template,
        $sauth,
        $helper
    ) {
        $this->config = $config;
        $this->request = $request;
        $this->template = $template;
        $this->sauth = $sauth;
        $this->helper = $helper;
        $this->userId = $sauth->userId;
        // $this->sauth->reject_non_dev('Error Code: a5e8ee80c7');
    }/*}}}*/

    public function markTopicForViolation()/*{{{*/
    {
        if (!$this->request->is_set_post('submit')) {
            return Response('', 400);
        }
        $reason = $this->request->variable('reason', '');
        $topicId = $this->request->variable('topicId', 0);
        $mark = $this->request->variable('mark', 0);
        $this->helper->markTopicForViolation($topicId, $mark, $reason);
        return new Response("Topic=${topicId} marked for violation.");
    }/*}}}*/
}
