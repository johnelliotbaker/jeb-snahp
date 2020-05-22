<?php
namespace jeb\snahp\Apps\PostCreator;

use \Symfony\Component\HttpFoundation\Response;
use \Symfony\Component\HttpFoundation\JsonResponse;
use jeb\snahp\Apps\PostCreator\PostCreatorHelper;

class PostCreatorController
{
    protected $db;/*{{{*/
    protected $user;
    protected $config;
    protected $request;
    protected $template;
    protected $container;
    protected $helper;
    protected $tbl;
    protected $sauth;
    protected $myHelper;
    public function __construct(
        $db,
        $user,
        $config,
        $request,
        $template,
        $container,
        $helper,
        $tbl,
        $sauth,
        $myHelper
    ) {
        $this->db = $db;
        $this->user = $user;
        $this->config = $config;
        $this->request = $request;
        $this->template = $template;
        $this->container = $container;
        $this->helper = $helper;
        $this->tbl = $tbl;
        $this->sauth = $sauth;
        $this->myHelper = $myHelper;
        $this->userId = (int) $this->user->data['user_id'];
        $this->sauth->reject_anon('Error Code: 5a372f177a');
        trigger_error("DISABLED. Error Code: 9471592439");
    }/*}}}*/

    public function respondCreate($forumId, $total)/*{{{*/
    {
        include_once 'includes/functions_posting.php';
        $mode = 'post';
        $username = "admin";
        $topic_type = 0;
        $poll_ary = [];
        $data_ary = [
            'topic_title' => 'TOPIC TITLE',
            'topic_id' => 0,
            'forum_id' => 13,
            'icon_id' => 0,
            'poster_id' => 2,
            'enable_sig' => 1,
            'enable_bbcode' => 1,
            'enable_smilies' => 1,
            'enable_urls' => 1,
            'enable_indexing' => 1,
            'message_md5' => '',
            'post_edit_user' => 0,
            'post_edit_locked' => 0,
            'bbcode_bitfield' => '',
            'bbcode_uid' => '',
            'message' => 'xyz',
            'topic_status' => 0
        ];
        [$start, $end] = [0, $start+$total];
        for ($i = $start; $i < $end; $i++) {
            $subject = "[TAG] ${i} ";
            $data_ary['topic_title'] = $subject;
            submit_post(
                $mode, $subject, $username, $topic_type,
                $poll_ary, $data_ary, $update_message = true,
                $update_search_index = true
            );
        }
        return new JsonResponse(["method"=>$subject]);
    }/*}}}*/

}
