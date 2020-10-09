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
        $this->style_type = 'light';
        $hour = (int) $this->user->format_date(time(), 'H');
        $time_of_day = $this->bannerConfig['time_of_day'][$hour];
        $p_banners = $this->bannerConfig[$this->style_type][$time_of_day];
        $banner_url = $p_banners[array_rand($p_banners)];
        $this->template->assign_vars(
            [
            'BANNER_IMG_URL' => $banner_url,
            'PRELOAD_BANNER_IMG_URLS' => $p_banners,
            ]
        );
    }



    public function selectBannerImage1()
    {
        $urls = [
            'https://i.imgur.com/b61V6KV.jpg',
            'https://i.imgur.com/z1adfkT.jpg',
            'https://i.imgur.com/omb4NpH.jpg',
            'https://i.imgur.com/wimLUJL.jpg',

            'https://i.imgur.com/wbBHKXW.jpg',
            'https://i.imgur.com/KTQH3Nf.jpg',
            'https://i.imgur.com/Fh6NtlT.jpg',
            'https://i.imgur.com/cutaz1m.jpg',

            'https://i.imgur.com/AqDxrhE.jpg',
            'https://i.imgur.com/vfenssY.jpg',
            'https://i.imgur.com/Qa9HzXd.jpg',
            'https://i.imgur.com/h6tDQhN.jpg',

            'https://i.imgur.com/kEy6uWa.jpg',
            'https://i.imgur.com/OYdW4il.jpg',
            'https://i.imgur.com/0QBO4ta.jpg',
            'https://i.imgur.com/L3QuCLJ.jpg',

            'https://i.imgur.com/L3QuCLJ.jpg',
            'https://i.imgur.com/lxvyPHt.jpg',
            'https://i.imgur.com/VB6j6Zu.jpg',
            'https://i.imgur.com/yxfyJAu.jpg',
        ];
        $index = array_rand($urls, 1);
        $this->template->assign_var('BANNER_IMG_URL', $urls[$index]);
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
