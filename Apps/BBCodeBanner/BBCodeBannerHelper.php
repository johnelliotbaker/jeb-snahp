<?php

namespace jeb\snahp\Apps\BBCodeBanner;

class BBCodeBannerHelper
{
    protected $db;/*{{{*/
    protected $template;
    public function __construct(
        $db,
        $template
    ) {
        $this->db = $db;
        $this->template = $template;
    }/*}}}*/

    public function embedBannerImageURL($topicData)/*{{{*/
    {
        $postId = $topicData['topic_first_post_id'];
        $text = $this->getPostText($postId);
        preg_match('#\[banner]</s>(.*?)<e>\[/banner]#', $text, $match);
        if (!$match) {
            return;
        }
        $this->template->assign_var('BANNER_IMG_URL', $match[1]);
    }/*}}}*/

    public function getPostText($postId)/*{{{*/
    {
        $postId = (int) $postId;
        $sql = 'SELECT post_text FROM ' . POSTS_TABLE . " WHERE post_id=${postId}";
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        return $row ? $row['post_text'] : '';
    }/*}}}*/
}
