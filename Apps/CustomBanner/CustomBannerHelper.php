<?php

namespace jeb\snahp\Apps\CustomBanner;

class CustomBannerHelper
{
    protected $db;/*{{{*/
    protected $user;
    protected $template;
    protected $bannerConfig;
    public function __construct(
        $db,
        $user,
        $template,
        $bannerConfig
    ) {
        $this->db = $db;
        $this->user = $user;
        $this->template = $template;
        $this->bannerConfig = $bannerConfig;
    }/*}}}*/

    public function selectBannerImage()
    {
        $hour = (int) $this->user->format_date(time(), 'H');
        $bannerURL = getDefault($this->bannerConfig, $hour, 'https://i.imgur.com/C3vCd0g.jpg');
        $this->template->assign_vars(
            [
            'BANNER_IMG_URL' => $bannerURL,
            // 'PRELOAD_BANNER_IMG_URLS' => $p_banners,
            ]
        );
    }

    public function embedBannerImageURL($topicData)/*{{{*/
    {
        $postId = $topicData['topic_first_post_id'];
        $text = $this->getPostText($postId);
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
