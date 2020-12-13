<?php

namespace jeb\snahp\Apps\CustomBanner;

class CustomBannerHelper
{
    protected $db;/*{{{*/
    protected $config;
    protected $request;
    protected $user;
    protected $template;
    protected $bannerConfig;
    public function __construct(
        $db,
        $request,
        $config,
        $user,
        $template,
        $bannerConfig
    ) {
        $this->db = $db;
        $this->request = $request;
        $this->config = $config;
        $this->user = $user;
        $this->template = $template;
        $this->bannerConfig = $bannerConfig;
    }/*}}}*/

    public function setVisibility($value, $cache=3600)/*{{{*/
    {
        $cookieName = 'snp_custom_banner_enable';
        $this->user->set_cookie($cookieName, $value, time() + $cache);
    }/*}}}*/

    public function getVisibility()/*{{{*/
    {
        $cookieName = 'snp_custom_banner_enable';
        $cookieFullName = $this->config['cookie_name'] . '_' . $cookieName;
        return $this->request->variable($cookieFullName, 0, false, \phpbb\request\request_interface::COOKIE);
    }/*}}}*/

    public function selectBannerImage()/*{{{*/
    {
        $hour = (int) $this->user->format_date(time(), 'H');
        $banner = getDefault($this->bannerConfig, $hour, 'https://i.imgur.com/C3vCd0g.jpg');
        $bannerURL = weightedArrayRand($banner, 1000000, 'url', 'p');
        $this->template->assign_vars(
            [
            'BANNER_IMG_URL' => $bannerURL,
            // 'PRELOAD_BANNER_IMG_URLS' => $p_banners,
            ]
        );
    }/*}}}*/

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

    public function toggleVisibility()/*{{{*/
    {
        $newVisibility = $this->getVisibility() ? 0 : 1;
        $this->setVisibility($newVisibility);
    }/*}}}*/

}

function weightedArrayRand($entry, $max=1000000, $valueKey=0, $probabilityKey=1)/*{{{*/
{
    if (!is_array($entry)) {
        return $entry;
    }
    $count = 0;
    // count really should grow like N
    while ($count++ < 10) {
        $item = $entry[array_rand($entry)];
        if (rand(0, $max) <= (int) $item[$probabilityKey]) {
            return $item[$valueKey];
        }
    }
    return $entry[0][$valueKey];
}/*}}}*/
