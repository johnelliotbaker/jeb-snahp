<?php
/**
 *
 * snahp. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2018
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace jeb\snahp\event;


use jeb\snahp\core\core;
use phpbb\auth\auth;
use phpbb\template\template;
use phpbb\config\config;
use phpbb\request\request_interface;
use phpbb\db\driver\driver_interface;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;


function closetags($html) {
    preg_match_all('#<([a-z]+)(?: .*)?(?<![/|/ ])>#iU', $html, $result);
    $openedtags = $result[1];
    preg_match_all('#</([a-z]+)>#iU', $html, $result);
    $closedtags = $result[1];
    $len_opened = count($openedtags);
    if (count($closedtags) == $len_opened) {
        return $html;
    }
    $openedtags = array_reverse($openedtags);
    for ($i=0; $i < $len_opened; $i++) {
        if (!in_array($openedtags[$i], $closedtags)) {
            $html .= '</'.$openedtags[$i].'>';
        } else {
            unset($closedtags[array_search($openedtags[$i], $closedtags)]);
        }
    }
    return $html;
}


function prn($var) {
    if (is_array($var))
    { foreach ($var as $k => $v) { echo "$k => "; prn($v); }
    } else { echo "$var<br>"; }
}


function fwrite($filepath, $var, $bNew=true)
{
    if ($bNew) file_put_contents($filepath, '');
    if (is_array($var))
    {
        foreach ($var as $k => $v)
        {
            file_put_contents($filepath, "$k => ", FILE_APPEND);
            fwrite($filepath, $v, false);
        }
    }
    else
    {
        file_put_contents($filepath, "$var\n", FILE_APPEND);
    }
}


/**
 * snahp Event listener.
 */
class main_listener extends core implements EventSubscriberInterface
{
    protected $auth;
    protected $request;
    protected $config;
    protected $db;
    protected $template;
    protected $table_prefix;

    public function __construct(
        auth $auth,
        request_interface $request,
        config $config,
        driver_interface $db,
        template $template,
        $table_prefix
    )
    {

        $this->auth         = $auth;
        $this->request      = $request;
        $this->config       = $config;
        $this->db           = $db;
        $this->template     = $template;
        $this->table_prefix = $table_prefix;
    }

    static public function getSubscribedEvents()
    {
        return array(
            'gfksx.thanksforposts.output_thanks_before'  => 'modify_avatar_thanks',
            'core.ucp_profile_modify_signature_sql_ary'  => 'modify_signature',
            'core.posting_modify_template_vars'          => array(
                array('modify_posting_for_imdb', 0),
                array('modify_posting_for_anilist', 0),
            ),
            'core.viewtopic_modify_post_row'             => 'disable_signature',
            'core.viewtopic_assign_template_vars_before' => 'insert_new_topic_button',
        );
    }

    public function insert_new_topic_button($event)
    {
        $forum_id = $this->request->variable("f", "");
        // Note that this means user must properly walk their way into
        // a forum. viewtopic.php?t=3 for example will not work.
        // This is done to reduce the number of sql calls.
        if ($forum_id && is_numeric($forum_id))
        {
            $this->template->assign_vars([
                "snp_forum_id" => $forum_id,
            ]);
        }
        // viewtopic_buttons_top_after.html may require theme specific configuration:
        // {% if T_THEME_NAME == 'prosilver' %}
        // <a href="./posting.php?mode=post&amp;f={snp_forum_id}" class="button" title="Post a new topic">
        //   <span>New Topic</span> <i class="icon fa-pencil fa-fw" aria-hidden="true"></i>
        // </a>
        // {% elseif T_THEME_NAME == 'basic' %}
    }

    public function disable_signature($event)
    {
        $t = $this->template;
        $pr = $event['post_row'];
        $poster_id = $pr['POSTER_ID'];
        $sql = 'SELECT group_id from ' . USERS_TABLE .
            ' WHERE user_id=' . $poster_id;
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $gid = $row['group_id'];
        $this->db->sql_freeresult($result);
        $sql = "SELECT snp_enable_signature from " . GROUPS_TABLE .
            " WHERE group_id =" . $gid;
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $bShowSignature = $row['snp_enable_signature'] ? true : false;
        $this->db->sql_freeresult($result);
        if (!$bShowSignature)
        {
            $pr['SIGNATURE'] = false;
            $event['post_row'] = $pr;
        }
    }

    public function modify_signature($event)
    {
        $t = $this->template;
        $pr = $event['post_row'];
        $user_id = $this->user->data['user_id'];
        $sql = 'SELECT group_id from ' . USERS_TABLE .
            ' WHERE user_id=' . $user_id;
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $gid = $row['group_id'];
        $this->db->sql_freeresult($result);
        $sql = "SELECT snp_signature_rows from " . GROUPS_TABLE .
            " WHERE group_id =" . $gid;
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $nSignatureRow = $row['snp_signature_rows'] or 0;
        $this->db->sql_freeresult($result);
        $sql_ary = $event['sql_ary'];
        $user_sig = &$sql_ary['user_sig'];
        $split = explode("\n", $user_sig);
        $split = array_slice($split, 0, $nSignatureRow);
        $user_sig = implode("\n", $split);
        $user_sig = closetags($user_sig);
        $event['sql_ary'] = $sql_ary;
    }

    public function modify_posting_for_anilist($event) 
    {
        $anime_forum_id = [13, 16,];
        $anime_forum_id = [2];
        $forum_id = $this->request->variable("f", "");
        if ($forum_id && is_numeric($forum_id) &&
            in_array($forum_id, $anime_forum_id))
        {
            $this->template->assign_vars([
                "bShowAnilist" => true,
            ]);
        }
    }

    public function modify_posting_for_imdb($event) 
    {
        global $user;
        $listing_forum_id = [4, 9, 10, 11, 12, 13, 14, 15, 24, 25, 26, 27, 29, 30, 31, 32, 33, 34, 35, 36, 37, 38, 39, 40, 41, 42, 43, 44, 45, 46, 47, 55, 56, 57, 58, 59, 60, 61, 62, 63, 64, 65, 66, 72, 73, 74, 75, 76, 77, 78, 79, 80, 81, 82,];
        $listing_forum_id = [2];
        $forum_id = $this->request->variable("f", "");
        $topic_id = $this->request->variable("t", "");
        if ($forum_id && is_numeric($forum_id) && !($topic_id) &&
            in_array($forum_id, $listing_forum_id))
        {
            $user_id = $user->data['user_id'];
            $sql = 'SELECT group_id FROM ' . USERS_TABLE . '
                WHERE user_id = ' . $user_id;
            $result = $this->db->sql_query($sql);
            $row = $this->db->sql_fetchrow($result);
            $gid = $row['group_id'];
            $this->db->sql_freeresult($result);
            $sql = 'SELECT snp_imdb_enable FROM ' . GROUPS_TABLE . '
                WHERE group_id = ' . $gid;
            $result = $this->db->sql_query($sql);
            $row = $this->db->sql_fetchrow($result);
            $bGroupEnable = $row['snp_imdb_enable'];
            $this->db->sql_freeresult($result);
            if ($bGroupEnable)
            {
                $this->template->assign_vars(["bShowImdb" => true,]);
            }
        }
    }

    public function modify_avatar_thanks($event) 
    {
        $poster_id = $event['poster_id'];
        $sql = 'SELECT snp_disable_avatar_thanks_link FROM ' . USERS_TABLE . ' WHERE user_id=' . $poster_id;
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        $b_disable_avatar_thanks_link = false;
        if ($row)
        {
            $b_disable_avatar_thanks_link= $row['snp_disable_avatar_thanks_link'];
        }

        if ($b_disable_avatar_thanks_link)
        {
            $event['u_receive_count_url'] = false;
            $event['u_give_count_url'] = false;
        }
    }
}
