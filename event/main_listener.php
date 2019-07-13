<?php

/*{{{*/
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
use jeb\snahp\core\base;
use phpbb\auth\auth;
use phpbb\template\template;
use phpbb\config\config;
use phpbb\request\request_interface;
use phpbb\db\driver\driver_interface;
use phpbb\notification\manager;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
/*}}}*/

function closetags($html) {/*{{{*/
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
}/*}}}*/

class main_listener extends base implements EventSubscriberInterface
{
    protected $table_prefix;
    public function __construct($table_prefix)/*{{{*/
    {
        $this->table_prefix = $table_prefix;
        $this->sql_limit          = 10;
        $this->notification_limit = 10;
        $this->at_prefix          = '@@';
    }/*}}}*/

    static public function getSubscribedEvents()/*{{{*/
    {
        return [
            'core.user_setup'                             => [
                ['print_version_on_footer', 0],
                ['include_donation_navlink', 0],
                ['include_quick_link', 0],
                ['setup_custom_css', 0],
                // ['setup_core_vars', 0],
            ],
            'core.user_setup_after'                            => [
                ['setup_core_vars', 0],
            ],
            'core.memberlist_prepare_profile_data'                => [
                ['setup_profile_variables', 0],
                ['show_achievements_in_profile', 0],
                ['show_reputation_in_profile', 0],
            ],
            'gfksx.thanksforposts.output_thanks_before'   => 'modify_avatar_thanks',
            'gfksx.thanksforposts.insert_thanks_before'   => 'insert_thanks',
            'core.display_forums_after'                   => 'show_thanks_top_list',
            'core.ucp_profile_modify_signature'           => 'modify_signature',
            'core.modify_posting_parameters'              => 'include_assets_before_posting',
            'core.modify_format_display_text_after'      => [
                ['process_curly_tags_for_preview', 0],
                ['process_emotes_for_preview', 2],
            ],
            'core.search_modify_tpl_ary'              => [
                ['process_curly_tags_for_search', 1],
            ],
            'core.viewtopic_modify_post_row'              => [
                ['setup_viewtopic_modify_post_row_data', 1000],
                ['easter_cluck', 1],
                ['block_zebra_foe_topicview', 1],
                ['show_requests_solved_avatar', 1],
                ['show_thanks_avatar', 1],
                ['show_bump_button', 1],
                ['disable_signature', 1],
                ['process_emotes', 2],
                ['process_curly_tags', 2],
                ['show_thanks_for_op', 2],
                ['show_achievements_in_avatar', 2],
                ['show_reputation_in_avatar', 2],
            ],
            'core.notification_manager_add_notifications' => 'notify_op_on_report',
            'core.modify_submit_post_data'                => [
                ['modify_quickreply_signature', 0],
            ],
            'core.posting_modify_submit_post_after'       => [
                ['notify_on_poke', 0],
            ],
            'core.posting_modify_message_text'            => [
                ['colorize_at', 0],
                ['modify_dice_roll', 0],
                ['disable_magic_url_on_gallery', 1],
            ],
            'core.viewtopic_assign_template_vars_before'  => [
                ['insert_new_topic_button',0],
                ['mark_topic_read',0],
                ['show_search_index_btn',0],
            ],
            'core.modify_posting_auth' => [
                ['block_zebra_foe_quote', 0],
            ],
            'core.viewforum_modify_topics_data' => [
                ['replace_with_host_icons_in_listings', 0]
            ],
            'core.ucp_pm_compose_modify_parse_before' => [
                ['remove_hide_in_pm_on_submit', 0]
            ],
            'core.memberlist_view_profile' => [
                ['show_inviter_in_profile', 0]
            ],
            'core.search_modify_param_before' => [
                ['search_modify_param_before', 0]
            ],
            'core.display_forums_before' => [
                ['encode_tags_on_display_forums', 0]
            ],
            'core.display_forums_modify_template_vars' => [
                ['decode_tags_on_display_forums', 0],
            ],
            'core.viewtopic_modify_post_data' => [
                ['embed_digg_controls_in_topic', 0],
            ],
            'core.notification_manager_add_notifications' => [
                ['disable_email_notification', 0],
            ],
        ];
    }/*}}}*/

    public function test($event)/*{{{*/
    {
    }/*}}}*/

    public function setup_viewtopic_modify_post_row_data($event)/*{{{*/
    {
        $tbl = $this->container->getParameter('jeb.snahp.tables');
        $post_row = $event['post_row'];
        $poster_id = (int) $post_row['POSTER_ID'];
        $post_id = (int) $post_row['POST_ID'];
        // Save poster user_data
        $sql = 'SELECT * FROM ' . USERS_TABLE . " WHERE user_id={$poster_id}";
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result, 1);
        $this->db->sql_freeresult($result);
        $this->poster_data = $row;
        // Get reputation counts
        $sql = 'SELECT COUNT(*) as count FROM ' . $tbl['reputation'] . " WHERE post_id={$post_id}";
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        $this->n_post_rep = $row['count'];
        // Check if reputaiton was given for this post
        $user_id = $this->user->data['user_id'];
        $sql = 'SELECT * FROM ' . $tbl['reputation'] . " WHERE giver_id={$user_id} AND post_id={$post_id}";
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        $this->b_rep_given = !!$row;
    }/*}}}*/

    public function setup_profile_variables($event)/*{{{*/
    {
        $visitor_id = (int) $this->user->data['user_id'];
        $data = $event['data'];
        $username = $data['username'];
        $profile_id = (int) $data['user_id'];
        $profile_username = $data['username'];
        $hidden_fields = [
            'snp_profile_id' => $profile_id,
            'snp_profile_username' => $profile_username,
        ];
        $s_hidden_fields = build_hidden_fields($hidden_fields);
        $this->template->assign_vars([
            'S_PROFILE_HIDDEN_FIELDS' => $s_hidden_fields,
            'S_PROFILE_USERNAME' => $profile_username,
        ]);
    }/*}}}*/

    public function show_reputation_in_profile($event)/*{{{*/
    {
        if (!$this->config['snp_rep_b_master'])
        {
            return false;
        }
        $visitor_id = (int) $this->user->data['user_id'];
        $data = $event['data'];
        $username = $data['username'];
        $b_public = $data['snp_rep_b_profile'];
        $profile_id = (int) $data['user_id'];
        if (!$this->is_mod() && $profile_id != $visitor_id && !$b_public)
        {
            // Block public if unless mod or profile owner
            return false;
        }
        $profile_username = $data['username'];
        $sql = 'SELECT snp_rep_n_received FROM ' . USERS_TABLE . " WHERE user_id={$profile_id}";
        $result = $this->db->sql_query($sql, 30);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        if (!$row || $row['snp_rep_n_received'] < 1)
        {
            return false;
        }
        $this->template->assign_vars([
            'N_REPUTATION' => $row['snp_rep_n_received'],
            'B_REPUTATION_PROFILE_PUBLIC' => $b_public,
        ]);
        $tbl = $this->container->getParameter('jeb.snahp.tables');
        $order_by = 'count DESC';
        $sql_array = [
            'SELECT'    => 'r.post_id, p.post_subject, p.topic_id, p.forum_id, COUNT(*) as count',
            'FROM'      => [ $tbl['reputation'] => 'r', ],
            'LEFT_JOIN' => [
                [
                    'FROM' => [POSTS_TABLE => 'p'],
                    'ON' => 'p.post_id=r.post_id'
                ]
            ],
            'WHERE'     => "r.poster_id={$profile_id}",
            'GROUP_BY'  => 'r.post_id',
            'ORDER_BY'  => 'count DESC',
        ];
        $sql = $this->db->sql_build_query('SELECT', $sql_array);
        $result = $this->db->sql_query_limit($sql, 10, 0, 30);
        $rowset = $this->db->sql_fetchrowset($result);
        $this->db->sql_freeresult($result);
        $i = 1;
        foreach($rowset as $row)
        {
            $data = [];
            $data['INDEX'] = $i;
            $data['COUNT'] = $row['count'];
            $data['FORUM_ID'] = $row['forum_id'];
            $data['TOPIC_ID'] = $row['topic_id'];
            $data['POST_ID'] = $row['post_id'];
            $data['POST_SUBJECT'] = $row['post_subject'];
            $this->template->assign_block_vars('reputation', $data);
            $i += 1;
        }
    }/*}}}*/

    public function show_reputation_in_avatar($event)/*{{{*/
    {
        if (!$this->config['snp_rep_b_master'])
        {
            return false;
        }
        $poster_data = $this->poster_data;
        if (!$poster_data)
        {
            return false;
        }
        $tbl = $this->container->getParameter('jeb.snahp.tables');
        $post_row = $event['post_row'];
        $poster_id = $post_row['POSTER_ID'];
        $b_public = $poster_data['snp_rep_b_avatar'];
        $user_id = $this->user->data['user_id'];
        // Hiding public view
        if ($b_public || $poster_id==$user_id || $this->is_mod())
        {
            $n_rep = $poster_data['snp_rep_n_received'];
            if ($n_rep > 0)
            {
                $res = [
                    $poster_data['snp_rep_n_received'],
                    !$b_public && ($this->is_mod() || $poster_id==$user_id) ? 'p' : ''];
                $strn = implode(' ', $res);
                $post_row['REPUTATION'] = $strn;
            }
        }
        if (!$this->b_rep_given)
        {
            $post_row['SHOW_REPUTATION_BTN'] = $this->user->data['user_id'] == $poster_id ? false : true;
        }
        $event['post_row'] = $post_row;
    }/*}}}*/

    public function show_achievements_in_profile($event)/*{{{*/
    {
        if (!$this->config['snp_achi_b_master'])
        {
            return false;
        }
        $data = $event['data'];
        $profile_username = $data['username'];
        $visitor_id = (int) $this->user->data['user_id'];
        $profile_id = $data['user_id'];
        $b_public = $data['snp_achi_b_profile'];
        if (!$this->is_mod() && $profile_id != $visitor_id && !$b_public)
        {
            // Block public if unless mod or profile owner
            return false;
        }
        $this->template->assign_var('B_ACHIEVEMENTS_PROFILE_PUBLIC', $b_public);
        $this->template->assign_var('B_ACHIEVEMENTS', true);
        $rowset = $this->select_user_achievements($profile_id, 10);
        if (!$rowset)
        {
            return false;
        }
        $params = $this->container->getParameter('jeb.snahp.avatar.achievements');
        $style_name = $this->select_style_name();
        $postrow = [];
        foreach($rowset as $row)
        {
            $data = [];
            $type = $row['type'];
            if (!array_key_exists($type, $params)) { continue; }
            $param = $params[$type];
            $title = $param['title'];
            $title = preg_replace('#{USERNAME}#', $profile_username, $title);
            $title = preg_replace('#s\'s#', 's\'', $title);
            $data['IMG_URL'] = $param['img']['url'][$style_name];
            $data['NAME']    = $param['name'];
            $data['TITLE']   = $title;
            $data['URL']     = $param['url'];
            $this->template->assign_block_vars('achievements', $data);
        }
    }/*}}}*/

    public function show_achievements_in_avatar($event)/*{{{*/
    {
        if (!$this->config['snp_achi_b_master'])
        {
            return false;
        }
        $poster_data = $this->poster_data;
        if (!$poster_data)
        {
            return false;
        }
        $tbl = $this->container->getParameter('jeb.snahp.tables');
        $user_id = $this->user->data['user_id'];
        $post_row = $event['post_row'];
        $post_author = $post_row['POST_AUTHOR'];
        $poster_id = $poster_data['user_id'];
        $b_public = $poster_data['snp_achi_b_avatar'];
        if (!$b_public)
        {
            // Block public
            return false;
        }
        $sql = 'SELECT * FROM ' . $tbl['achievements'] . " WHERE user_id={$poster_id}";
        $result = $this->db->sql_query($sql, 5);
        $rowset = $this->db->sql_fetchrowset($result);
        $this->db->sql_freeresult($result);
        if (!$rowset)
        {
            return false;
        }
        $i_achi = rand(0, count($rowset)-1);
        $row = $rowset[$i_achi];
        $type = $row['type'];
        $style_name = $this->select_style_name();
        $params = $this->container->getParameter('jeb.snahp.avatar.achievements');
        if (!array_key_exists($type, $params))
        {
            return false;
        }
        if (rand(0,100) > (int) $params[$type]['probability'])
        {
            return false;
        }
        $title = preg_replace('#{USERNAME}#', $post_author, $params[$type]['title']);
        $title = preg_replace('#s\'s#', 's\'', $title);
        $post_row['U_AVATAR_ACHIEVEMENTS_IMG'] = $params[$type]['img']['url'][$style_name];
        $post_row['S_AVATAR_ACHIEVEMENTS_TITLE'] = $title;
        $post_row['U_AVATAR_ACHIEVEMENTS'] =  $params[$type]['url'];
        $event['post_row'] = $post_row;
    }/*}}}*/

    public function print_version_on_footer($event)/*{{{*/
    {
        $ext = $this->container->get('ext.manager');
        $md_manager = $ext->create_extension_metadata_manager('jeb/snahp');
        $data = $md_manager->get_metadata('all');
        $hash = substr($data['extra']['version'], 0, 5);
        $version = $data['version'];
        $this->template->assign_vars([
            'S_SNAHP_VERSION' => "v{$version} [{$hash}]",
        ]);
    }/*}}}*/

    public function disable_email_notification($event)/*{{{*/
    {
        $type = $event['notification_type_name'];
        if (substr($type, 0, 3) == 'jeb')
        {
            return false;
        }
        $notify_users = $event['notify_users'];
        foreach($notify_users as $key=>$user)
        {
            foreach($user as $i=>$method)
            {
                if($method==='notification.method.email')
                {
                    unset($notify_users[$key][$i]);
                }
            }
        }
        $event['notify_users'] = $notify_users;
    }/*}}}*/

    public function embed_digg_controls_in_topic($event)/*{{{*/
    {
        if (!$this->config['snp_b_snahp_notify'] || !$this->config['snp_digg_b_master'] || !$this->config['snp_digg_b_notify'])
        {
            return false;
        }
        $cooldown = 2;
        $user_id = $this->user->data['user_id'];
        $topic_data = $event['topic_data'];
        $b_listing = $this->is_listing($topic_data, 'topic_data');
        if (!$b_listing)
        {
            return false;
        }
        $topic_id = $topic_data['topic_id'];
        $event['topic_data'] = $topic_data;
        $b_op = $this->is_op($topic_data);
        $master_data = $this->select_digg_master($topic_id);
        $b_digg_master = false;
        $b_can_unregister = false;
        $count = 0;
        $state = 0;
        if ($master_data)
        {
            $b_digg_master = true;
            $slave_data = $this->select_digg_slave_count($topic_id, $cooldown=$cooldown);
            $count = $slave_data['count'];
            $state = 1;
            if ($b_op)
            {
                $b_can_unregister = true;
            }
        }
        if (!$b_op)
        {
            if ($b_digg_master)
            {
                $state = 2;
                $slave_data = $this->select_digg_slave($topic_id, $where="user_id={$user_id}");
                if ($slave_data)
                {
                    $state = 3;
                }
            }
        }
        switch ($state)
        {
        case 2:
            $s_digg_name = 'digg';
            $s_digg_url_mode = 'subscribe';
            break;
        case 0:
            $s_digg_name = 'register';
            $s_digg_url_mode = 'register';
            break;
        case 3:
            $s_digg_name = 'undigg';
            $s_digg_url_mode = 'unsubscribe';
            break;
        case 1:
            $s_digg_name = 'broadcast';
            $s_digg_url_mode = 'broadcast';
            break;
        default:
            $s_digg_name = 'default';
            $s_digg_url_mode = 'default';
        }
        $this->template->assign_vars([
            'TOPIC_FIRST_POST_ID' => $topic_data['topic_first_post_id'],
            'B_OP' => $b_op,
            'B_DIGG_MASTER' => $b_digg_master,
            'S_DIGG_NAME' => $s_digg_name,
            'N_DIGG_COUNT' => $count,
            'S_DIGG_URL_MODE' => $s_digg_url_mode,
            'B_DIGG_CAN_UNREGISTER' => $b_can_unregister,
        ]);
    }/*}}}*/

    public function disable_magic_url_on_gallery($event)/*{{{*/
    {
        $mp = $event['message_parser'];
        $message = $mp->message;
        $b_match = preg_match('#{gallery_.*?}#s', htmlspecialchars_decode($message), $match);
        if ($b_match)
        {
            $post_data = $event['post_data'];
            $post_data['enable_magic_url'] = 0;
            $post_data['enable_urls'] = 0;
            $event['post_data'] = $post_data;
        }
    }/*}}}*/

    public function encode_tags_on_display_forums($event)/*{{{*/
    {
        // For showing the minified tags on forum listings
        $forum_rows = $event['forum_rows'];
        if (is_array($forum_rows))
        {
            foreach($forum_rows as $k=>$row)
            {
                $type = $row['forum_type'];
                if ($type==0)
                {
                    continue;
                }
                $tmp = $row['forum_last_post_subject'];
                $tmp = $this->encode_tags($tmp);
                $row['forum_last_post_subject'] = $tmp;
                $forum_rows[$k] = $row;
            }
            $event['forum_rows'] = $forum_rows;
        }
    }/*}}}*/

    public function decode_tags_on_display_forums($event)/*{{{*/
    {
        $forum_row = $event['forum_row'];
        if (is_array($forum_row))
        {
            $tmp = $forum_row['LAST_POST_SUBJECT_TRUNCATED'];
            $tmp = $this->decode_tags($tmp);
            $forum_row['LAST_POST_SUBJECT_TRUNCATED'] = $tmp;
            $event['forum_row'] = $forum_row;
        }
    }/*}}}*/

    public function search_modify_param_before($event)/*{{{*/
    {
        $sort_by_sql = $event['sort_by_sql'];
        $sort_by_sql += ['x' => 't.topic_time'];
        $event['sort_by_sql'] = $sort_by_sql;
    }/*}}}*/

    public function show_inviter_in_profile($event)/*{{{*/
    {
        if ($this->is_mod())
        {
            $member = $event['member'];
            $row = $this->select_invitee((int) $member['user_id']);
            if (!$row)
            {
                $inviter_strn = 'The First of Her Kind';
            }
            else
            {
                $inviter_id = $row['inviter_id'];
                $inviter = $this->select_user($inviter_id);
                $inviter_strn = $this->make_username($inviter);
            }
            $this->template->assign_vars([
                'IS_MOD' => true,
                'INVITER_STRN' => $inviter_strn,
            ]);
        }
    }/*}}}*/

    public function remove_hide_in_pm_on_submit($event)/*{{{*/
    {
        $mp = $event['message_parser'];
        $msg = &$mp->message;
        $msg = preg_replace('/(\[hide=?"?)([^\]"]*)("?\])([^\[]*)(\[\/hide\])/i', 'HIDDEN CONTENT WAS REMOVED', $msg);
    }/*}}}*/

    public function setup_core_vars($event)/*{{{*/
    {
        $n_rep = $this->user->data['snp_rep_n_available'];
        $rep_giveaway_last_time = (int) $this->config['snp_rep_giveaway_last_time'];
        $rep_giveaway_duration  = (int) $this->config['snp_rep_giveaway_duration'];
        $t_rep_next = $this->user->format_date($rep_giveaway_last_time + $rep_giveaway_duration);
        $this->user->format_date(0);
        $hidden_fields = [
            'snp_user_id' => $this->user->data['user_id'],
            'snp_servername' => $this->config['server_name'],
            'snp_cookie_prefix' => $this->config['cookie_name'] . '_',
        ];
        $s_hidden_fields = build_hidden_fields($hidden_fields);
        $this->template->assign_vars([
            'S_HIDDEN_FIELDS' => $s_hidden_fields,
            'S_HIDDEN_FIELDS_ALT' => $s_hidden_fields,
            'N_REP_AVAILABLE' => $n_rep,
            'T_REP_NEXT' => $t_rep_next,
        ]);
    }/*}}}*/

    public function replace_with_host_icons_in_listings($event)/*{{{*/
    {
        $cache_time = 30;
        $fid = $event['forum_id'];
        $fid_listings = $this->config['snp_fid_listings'];
        $fid_listings = $this->select_subforum($fid_listings, $cache_time);
        if (!in_array($fid, $fid_listings)) return false;
        $rowset = $event['rowset'];
        if (!$rowset) return false;
        foreach($rowset as $key => $row)
        {
            $tt = $row['topic_title'];
            $row['topic_title'] = $this->add_host_icon($tt, 'open');
            $rowset[$key] = $row;
        }
        $event['rowset'] = $rowset;
    }/*}}}*/

    public function show_thanks_for_op($event)/*{{{*/
    {
        $cachetime = 30;
        $b_master = $this->config['snp_thanks_b_enable'];
        $b_op = $this->config['snp_thanks_b_op'];
        if (!$b_master || !$b_op)
        {
            return false;
        }
        $post_row = $event['post_row'];
        $post_id = $post_row['POST_ID'];
        $topic_data = $event['topic_data'];
        $topic_id = $topic_data['topic_id'];
        $topic_poster = $topic_data['topic_poster'];
        $thanks_total = 0;
        if ($this->poster_data['snp_thanks_b_topic'] || $this->is_op($topic_data) || $this->is_mod())
        {
            $thanks_total = $this->select_thanks_for_op($topic_id, $cachetime);
        }
        if ($post_id != $topic_data['topic_first_post_id'])
        {
            $thanks_total = 0;
        }
        $rep_total = $this->select_rep_total_for_post($post_id);
        $post_row['N_REP'] = $rep_total;
        $post_row['N_THANKS'] = $thanks_total;
        $event['post_row'] = $post_row;
    }/*}}}*/

    public function show_search_index_btn($event)/*{{{*/
    {
        $topic_data = $event['topic_data'];
        if ($this->is_search_enhancer_allowed($topic_data))
        {
            $this->template->assign_var('SNP_SEARCH_INDEX_B_ENABLE', true);
        }
    }/*}}}*/

    public function mark_topic_read($event)/*{{{*/
    {
        // phpbb_bump_topic($forum_id, $topic_id, $post_data, $bump_time = false);
        $forum_id = $event['forum_id'];
        $topic_id = $event['topic_id'];
        $time = time();
        markread('post', $forum_id, $topic_id, $time);
        markread('topic', $forum_id, $topic_id, $time);
    }/*}}}*/

    public function block_zebra_foe_quote($event)/*{{{*/
    {
        $snp_zebra_b_master = $this->config['snp_zebra_b_master'];
        if (!$snp_zebra_b_master) return false;
        $mode = $event['mode'];
        $user_id = $this->user->data['user_id'];
        $topic_id = $event['topic_id'];
        $topic_data = $this->select_topic($topic_id);
        if (!$topic_data) return false;
        $topic_poster = $topic_data['topic_poster'];
        $sql = 'SELECT 1 FROM ' . ZEBRA_TABLE . '
            WHERE foe=1 AND user_id=' . $topic_poster . ' AND zebra_id=' . $user_id;
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        if ($row && $mode=='quote')
        {
            trigger_error('Sorry, quoting in this topic is currently disabled.');
        }
    }/*}}}*/

    public function block_zebra_foe_topicview($event)/*{{{*/
    {

        $i_row = $event['current_row_number'];
        if ($i_row > 0) return false;
        $snp_zebra_b_master = $this->config['snp_zebra_b_master'];
        if (!$snp_zebra_b_master) return false;
        $topic_data = $event['topic_data'];
        $user_id = $this->user->data['user_id'];
        $topic_poster = $topic_data['topic_poster'];
        $sql = 'SELECT 1 FROM ' . ZEBRA_TABLE . '
            WHERE foe=1 AND user_id=' . $topic_poster . ' AND zebra_id=' . $user_id;
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        $topic_first_poster_name = $topic_data['topic_first_poster_name'];
        $topic_first_poster_colour = $topic_data['topic_first_poster_colour'];
        if ($row)
        {
            $this->template->assign_vars([ 'ZEBRA_BLOCK' => true, ]);
            $post_row = $event['post_row'];
            $username = '<span style="color:#'. $topic_first_poster_colour .'">' . $topic_first_poster_name . '</span>';
            $post_row['MESSAGE'] = 'Sorry, this post is currently unavailable for viewing.';
            $event['post_row'] = $post_row;
        }
    }/*}}}*/

    public function show_thanks_top_list($event)/*{{{*/
    {
        $uid = $this->user->data['user_id'];
        if ($uid == ANONYMOUS) return false;
        if (!$this->config['snp_thanks_b_enable']) return false;
        if (!$this->config['snp_thanks_b_toplist']) return false;
        $sql = 'SELECT user_id, username, user_colour, snp_thanks_n_received FROM ' . USERS_TABLE . '
            ORDER BY snp_thanks_n_received DESC';
        $result = $this->db->sql_query_limit($sql, 10, 0);
        $rowset = $this->db->sql_fetchrowset($result);
        $this->db->sql_freeresult($result);
        $n_row = count($rowset);
        foreach ($rowset as $key => $row)
        {
            $delim = ($n_row==$key+1) ? '' : ',';
            $data = [
                'USERNAME'              => $row['username'],
                'USER_COLOUR'           => $row['user_colour'],
                'USER_ID'               => $row['user_id'],
                'SNP_THANKS_N_RECEIVED' => $row['snp_thanks_n_received'],
                'SNP_THANKS_DELIM'      => $delim,
            ];
            $this->template->assign_block_vars('A_TOP_LIST', $data);
        }
        $this->template->assign_vars([
            'SNP_THANKS_B_TOPLIST' => true,
            'SNP_THANKS_N_TOTAL' => $n_row,
        ]);
    }/*}}}*/

    public function insert_thanks($event)/*{{{*/
    {
        if (!$this->config['snp_thanks_b_enable']) return false;
        $from_id = $event['from_id'];
        $to_id = $event['to_id'];
        $sql = 'UPDATE ' . USERS_TABLE . ' SET snp_thanks_n_given=snp_thanks_n_given+1 WHERE user_id=' . $from_id;
        $this->db->sql_query($sql);
        $sql = 'UPDATE ' . USERS_TABLE . ' SET snp_thanks_n_received=snp_thanks_n_received+1 WHERE user_id=' . $to_id;
        $this->db->sql_query($sql);
    }/*}}}*/

    public function show_thanks_avatar($event)/*{{{*/
    {
        if (!$this->config['snp_thanks_b_enable']) return false;
        if (!$this->config['snp_thanks_b_avatar']) return false;
        $post_row = $event['post_row'];
        $user_data = $this->poster_data;
        $post_row['THANKS_GIVEN'] = $user_data['snp_thanks_n_given'];
        $post_row['THANKS_RECEIVED'] = $user_data['snp_thanks_n_received'];
        $event['post_row'] = $post_row;
        $this->template->assign_vars([
            'B_SHOW_THANKS_AVATAR' => true,
        ]);
    }/*}}}*/

    public function show_requests_solved_avatar($event)/*{{{*/
    {
        if (!$this->config['snp_req_b_avatar']) return false;
        $post_row = $event['post_row'];
        $poster_data = $this->poster_data;
        $post_row['REQUESTS_SOLVED'] = $poster_data['snp_req_n_solve'];
        $event['post_row'] = $post_row;
        $this->template->assign_vars([
            'B_SHOW_REQUESTS_SOLVED' => true,
        ]);
    }/*}}}*/

    public function easter_cluck($event)/*{{{*/
    {
        $snp_easter_b_chicken = $this->config['snp_easter_b_chicken'];
        if (!$snp_easter_b_chicken) return false;
        include_once('ext/jeb/snahp/core/chicken.php');
        $username = $this->user->data['username'];
        $motd_message = 'Hello ' . $username . ', thank you very much for being an important member of our community.<br>';
        $chance = $this->config['snp_easter_chicken_chance'];
        $MAX_STRN_LEN = 500;
        $topic_data = $event['topic_data'];
        $tt = $topic_data['topic_title'];
        $post_row = $event['post_row'];
        $strn = $post_row['MESSAGE'];
        $rand = rand(0, $chance);
        if ($rand < 1 && strlen($strn) < $MAX_STRN_LEN)
        {
            $strn = string2cluck($strn);
            $post_row['MESSAGE'] = $motd_message . $strn;
            $event['post_row'] = $post_row;
        }
    }/*}}}*/

    public function show_bump_button($event)/*{{{*/
    {
        $snp_bump_b_topic = $this->config['snp_bump_b_topic'];
        if (!$snp_bump_b_topic) return false;
        $topic_data = $event['topic_data'];
        $tid = $topic_data['topic_id'];
        $i_row = $event['current_row_number'];
        if ($i_row > 0) return false;
        $forum_id = $topic_data['forum_id'];
        $fid_listings = $this->config['snp_fid_listings'];
        $a_fid = $this->select_subforum($fid_listings);
        if (!in_array($forum_id, $a_fid)) return false;
        $user_bump_data = $this->get_bump_permission($tid);
        $this->template->assign_vars([
            'B_SHOW_BUMP_ENABLER' => $user_bump_data['b_enable'],
            'B_SHOW_BUMP_DISABLER' => $user_bump_data['b_disable'],
            'B_SHOW_BUMP' => $user_bump_data['b_bump'],
        ]);
        return false;
    }/*}}}*/

    public function process_curly_tags_for_preview($event)/*{{{*/
    {
        // For properly rendering snahp tags in post previews
        include_once('includes/functions_content.php');
        $text = $event['text'];
        $text = $this->interpolate_curly_tags($text);
        $event['text'] = $text;
    }/*}}}*/

    public function process_curly_tags_for_search($event)/*{{{*/
    {
        // For properly rendering snahp tags for within topic search
        $tpl_ary = $event['tpl_ary'];
        $message = &$tpl_ary['MESSAGE'];
        $message = $this->interpolate_curly_tags($message);
        $event['tpl_ary'] = $tpl_ary;
    }/*}}}*/

    public function process_curly_tags($event)/*{{{*/
    {
        $post_row = $event['post_row'];
        $message = &$post_row['MESSAGE'];
        $message = $this->interpolate_curly_tags($message);
        $event['post_row'] = $post_row;
    }/*}}}*/

    private function preg_replace_emotes($message)/*{{{*/
    {
        $emotes = $this->container->getParameter('jeb.snahp.emotes');
        $ptn = '/#(e_\w+)#/';
        $b_match = preg_match_all($ptn, $message, $a_match);
        if (!$b_match)
        {
            return $message;
        }
        foreach($a_match[1] as $keyword)
        {
            if (array_key_exists($keyword, $emotes))
            {
                $repl = "<img class='emotes_default' src='{$emotes[$keyword]['url']}'></img>";
                $curr_ptn = "/#($keyword)#/";
                $message = preg_replace($curr_ptn, $repl, $message);
            }
        }
        return $message;
    }/*}}}*/

    public function process_emotes_for_preview($event)/*{{{*/
    {
        $text = $event['text'];
        $this->preg_replace_emotes($text);
        $event['text'] = $text;
    }/*}}}*/

    public function process_emotes($event)/*{{{*/
    {
        $emotes = $this->container->getParameter('jeb.snahp.emotes');
        $post_row = $event['post_row'];
        $message = &$post_row['MESSAGE'];
        $message = $this->preg_replace_emotes($message);
        $event['post_row'] = $post_row;
    }/*}}}*/

    public function setup_custom_css($event)/*{{{*/
    {
        $user_style = $this->user->data['user_style'];
        $sql = 'SELECT style_name FROM ' . $this->table_prefix . 'styles
            WHERE style_id=' . $user_style;
        $result = $this->db->sql_query_limit($sql, 1);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        $style_name = $row['style_name'];
        switch ($style_name)
        {
        case 'Acieeed!':
            $this->template->assign_var('STYLE_NAME', 'acieeed');
            break;
        case 'Basic':
            $this->template->assign_var('STYLE_NAME', 'basic');
            break;
        case 'Hexagon':
            $this->template->assign_var('STYLE_NAME', 'hexagon');
            break;
        case 'prosilver':
        default:
        $this->template->assign_var('STYLE_NAME', 'prosilver');
        break;
        }
    }/*}}}*/

    public function modify_quickreply_signature($event)/*{{{*/
    {
        // quickreply adds hidden checkbox with name=attach_sig to always
        // enable signatures. To make this a choice
        // 1) quickreply_editor_message_after.html to add checkbox
        // 2) utility.js to remove the hidden checkbox
        // 3) this event listener to get checkbox data and set enable_sig
        $varnames = $this->request->variable_names();
        if (!in_array('qr_exists', $varnames))
            return false;
        $var = $this->request->variable('qr_attach_sig', '0');
        $enable_sig = $var ? 1 : 0;
        $data = $event['data'];
        if ($enable_sig){
            $data['enable_sig'] = 1;
        }
        else
        {
            $data['enable_sig'] = 0;
        }
        $event['data'] = $data;
    }/*}}}*/

    public function include_quick_link($event)/*{{{*/
    {
        $user_id = $this->config['snp_ql_your_topics'] ? $this->user->data['user_id'] : -1;
        $this->template->assign_vars([
            'B_SHOW_QL_REPLIES' => $this->config['snp_ql_fav_b_replies'],
            'B_SHOW_QL_VIEWS' => $this->config['snp_ql_fav_b_views'],
            'B_SHOW_QL_TIME' => $this->config['snp_ql_fav_b_time'],
            'B_SHOW_THANKS_GIVEN' => $this->config['snp_ql_thanks_given'],
            'B_SHOW_BOOKMARK' => $this->config['snp_ql_ucp_bookmark'],
            'B_SHOW_OPEN_REQUESTS' => $this->config['snp_ql_req_open_requests'],
            'B_SHOW_ACCEPTED_REQUESTS' => $this->config['snp_ql_req_accepted_requests'],
            'S_USER_ID' => $user_id,
        ]);
    }/*}}}*/

    public function include_donation_navlink($event)/*{{{*/
    {
        $this->template->assign_vars([
            'B_SHOW_DONATION_NAVLINK' => $this->config['snp_don_b_show_navlink'],
            'DON_URL' => $this->config['snp_don_url'],
        ]);
    }/*}}}*/

    public function get_user_string_from_usernames_sql($aUserdata, $prepend='', $bDullBlocked=false)/*{{{*/
    {
        if (!$aUserdata)
            return [];
        foreach ($aUserdata as $key => $row)
        {
            $username_clean = $row['username_clean'];
            $username = $row['username'];
            $uid = $row['user_id'];
            if ($bDullBlocked && !$row['snp_enable_at_notify'])
                $color = '555588';
            else
                $color = $row['user_colour'];
            if (!$color) $color = '000000';
            $username_string = "[color=#$color][b]$prepend$username".'[/b][/color]';
            $a_user_string[$username_clean] = $username_string;
        }
        return $a_user_string;
    }/*}}}*/

    public function get_user_data($aUsername)/*{{{*/
    {
        $sql = 'SELECT * FROM ' . USERS_TABLE . ' WHERE ' .
            $this->db->sql_in_set('username_clean', $aUsername);
        $result = $this->db->sql_query_limit($sql, $this->sql_limit);
        while ($row = $this->db->sql_fetchrow($result))
        {
            $data[] = $row;
        }
        $this->db->sql_freeresult($result);
        return $data;
    }/*}}}*/

    public function modify_dice_roll($event)/*{{{*/
    {
        $mp = $event['message_parser'];
        $message = &$mp->message;
        $message = strip_tags($message);
        preg_match_all('/#roll#/', $message, $matchall);
        $num = sprintf('%04d', rand(0,100));
        $a = '/#roll#/';
        $b = '[center][fimg=250,250]' . 'https://raw.githubusercontent.com/codexologist/img/master/img/roll/ishihara_' . $num . '.png' . '[/fimg][/center]';
        $message = preg_replace($a, $b, $message);
    }/*}}}*/

    public function colorize_at($event)/*{{{*/
    {
        if (!$this->config['snp_b_snahp_notify'])
            return false;
        if (!$this->config['snp_b_notify_on_poke'])
            return false;
        $at_prefix = $this->at_prefix;
        $mp = $event['message_parser'];
        $message = &$mp->message;
        $message = strip_tags($message);
        preg_match_all('#' . $at_prefix . '([A-Za-z0-9_\-]+)#is', $message, $matchall);
        // Collect Usernames
        $aUsername = [];
        foreach($matchall[1] as $match) $aUsername[$match] = utf8_clean_string($match);
        if (!$aUsername) return;
        $aUserdata = $this->get_user_data($aUsername);
        $aUserString = $this->get_user_string_from_usernames_sql($aUserdata, $at_prefix, true);
        array_multisort(array_map('strlen', $aUsername), $aUsername);
        $aUsername = array_reverse($aUsername);
        foreach($aUsername as $username_in_msg => $username_clean)
        {
            $b = $aUserString[$username_clean] . ' ';
            // $a = '#(?<!])'. $at_prefix . $username_in_msg . '#is';
            $a = '#(?<!])'. $at_prefix . $username_clean . '#is';
            $message = preg_replace($a, $b, $message);
        }
    }/*}}}*/

    public function notify_on_poke($event)/*{{{*/
    {
        if (!$this->config['snp_b_snahp_notify'])
            return false;
        if (!$this->config['snp_b_notify_on_poke'])
            return false;
        $at_prefix = $this->at_prefix;
        $data     = $event['data'];
        $tid      = $data['topic_id'];
        $fid      = $data['forum_id'];
        $pid      = $data['post_id'];
        $message  = strip_tags($data['message']);
        include_once('ext/jeb/snahp/event/utility.php');
        $message = filter_quote($message);
        preg_match_all('#' . $at_prefix .'([A-Za-z0-9_\-]+)#is', $message, $matchall);
        // Collect Usernames
        foreach($matchall[1] as $match)
        {
            $aUsername[$match] = utf8_clean_string($match);
        }
        if (!$aUsername) return;
        // Build sql query
        $count = 0;
        $user_id         = $this->user->data['user_id'];
        $username        = $this->user->data['username'];
        $color           = $this->user->data['user_colour'];
        $username_string = get_username_string('no_profile', $user_id, $username, $color);
        $aReceiverData = $this->get_user_data($aUsername);
        foreach ($aReceiverData as $row)
        {
            // If too many notifications are being sent, stop
            if ($count > $this->notification_limit) break;
            // If user doesn't want to receive notify skip this user
            if (!$row['snp_enable_at_notify']) continue;
            $count++;
            $receiver_id     = $row['user_id'];
            $receiver_name   = $row['username'];
            $receiver_color  = $row['user_colour'];
            $receiver_string = get_username_string('no_profile', $receiver_id, $receiver_name, $receiver_color);
            $text            = $username_string . ' poked @' . $receiver_string;
            $type            = 'at';
            $data = array(
                'user_id'      => $user_id,
                'username'     => $username,
                'post_id'      => $pid,
                'poster_id'    => $receiver_id,
                'topic_id'     => $tid,
                'forum_id'     => $fid,
                'time'         => time(),
                'post_subject' => $text,
                'type'         => $type,
            );
            $this->notification->add_notifications (
                'jeb.snahp.notification.type.basic', $data
            );
        }
    }/*}}}*/

    public function notify_op_on_report($event)/*{{{*/
    {
        if (!$this->config['snp_b_snahp_notify'])
            return false;
        if (!$this->config['snp_b_notify_op_on_report'])
            return false;
        // data, notification_type_name, notify_users, options
        $data         = $event['data'];
        $notification_type_name = $event['notification_type_name'];
        switch($notification_type_name)
        {
        case 'notification.type.report_post':
            $tid          = $data['topic_id'];
            $fid          = $data['forum_id'];
            $pid          = $data['post_id'];
            $topic_poster = $data['poster_id'];
            // When post is reported, send notification to OP
            $reason = $data['reason_description'];
            $aPattern = [
                '/contains links to illegal or pirated software./',
                '/post has dead links or an incorrect password/',
            ];
            foreach ($aPattern as $pattern)
            {
                $match = preg_match($pattern, $reason);
                if ($match)
                {
                    $text = 'This post was reported for: broken link.';
                    $data = array(
                        'user_id'      => $topic_poster,
                        'post_id'      => $pid,
                        'poster_id'    => $topic_poster,
                        'topic_id'     => $tid,
                        'forum_id'     => $fid,
                        'time'         => time(),
                        'post_subject' => $text,
                        'type'         => 'open_report',
                    );
                    $this->notification->add_notifications(array(
                        'jeb.snahp.notification.type.basic',
                    ), $data);
                }
            }
            break;
        case 'notification.type.report_post_closed':
            $tid          = $data['topic_id'];
            $fid          = $data['forum_id'];
            $pid          = $data['post_id'];
            $topic_poster = $data['poster_id'];
            // When report is closed by mod, remove the notification
            $pre = $this->table_prefix;
            $sql = 'SELECT * FROM ' . $pre . 'notifications as n 
                LEFT JOIN ' . $pre . 'notification_types as t 
                ON n.notification_type_id=t.notification_type_id
                WHERE notification_type_name="jeb.snahp.notification.type.basic" AND
                item_id=' . $pid;
            $result = $this->db->sql_query($sql);
            if ($row = $this->db->sql_fetchrow($result))
            {
                $nid = $row['notification_id'];
            }
            $this->db->sql_freeresult($result);
            if (isset($nid))
            {
                $sql = 'DELETE FROM ' . $pre . 'notifications 
                    WHERE notification_id=' . $nid;
                $result = $this->db->sql_query($sql);
            }
            break;
        }
    }/*}}}*/

    public function include_assets_before_posting($event)/*{{{*/
    {
        $forum_id = (int) $this->request->variable('f', '');
        $topic_id = $this->request->variable('t', '');
        $pg_names = ['anime', 'listing', 'book', 'game', 'mydramalist'];
        if ($forum_id && !($topic_id))
        {
            foreach ($pg_names as $pg_name)
            {
                $fid_allowed[$pg_name] = explode(',', $this->config['snp_pg_fid_' . $pg_name]);
            }
            $user_id = $this->user->data['user_id'];
            $gid = $this->user->data['group_id'];
            $sql = 'SELECT snp_imdb_enable, snp_anilist_enable,
                snp_googlebooks_enable, snp_gamespot_enable,
                snp_customtemplate_enable, snp_mydramalist_enable
                FROM ' . GROUPS_TABLE . '
                WHERE group_id = ' . $gid;
            $result = $this->db->sql_query($sql);
            $row = $this->db->sql_fetchrow($result);
            $bGroupEnable = $row['snp_imdb_enable'];
            $this->db->sql_freeresult($result);

            if ($row['snp_imdb_enable'] && in_array($forum_id, $fid_allowed['listing']))
                $this->template->assign_vars(['B_SHOW_IMDB' => true,]);

            if ($row['snp_anilist_enable'] && in_array($forum_id, $fid_allowed['anime']))
                $this->template->assign_vars(['B_SHOW_ANILIST' => true,]);

            if ($row['snp_googlebooks_enable'] && in_array($forum_id, $fid_allowed['book']))
                $this->template->assign_vars(['B_SHOW_BOOKS' => true,]);

            if ($row['snp_gamespot_enable'] && in_array($forum_id, $fid_allowed['game']))
                $this->template->assign_vars(['B_SHOW_GAMES' => true,]);

            if ($row['snp_mydramalist_enable'] && in_array($forum_id, $fid_allowed['mydramalist']))
                $this->template->assign_vars(['B_SHOW_MYDRAMALIST' => true,]);

            if ($row['snp_customtemplate_enable'])
                $this->template->assign_vars(['B_SHOW_CUSTOM_TPL' => true,]);
        }
    }/*}}}*/

    public function insert_new_topic_button($event)/*{{{*/
    {
        $forum_id = $this->request->variable('f', '');
        // Note that this means user must properly walk their way into
        // a forum. viewtopic.php?t=3 for example will not work.
        // This is done to reduce the number of sql calls.
        if ($forum_id && is_numeric($forum_id))
        {
            $this->template->assign_vars([
                'snp_forum_id' => $forum_id,
            ]);
        }
        // viewtopic_buttons_top_after.html may require theme specific configuration:
        // {% if T_THEME_NAME == 'prosilver' %}
        // <a href="./posting.php?mode=post&amp;f={snp_forum_id}" class="button" title="Post a new topic">
        //   <span>New Topic</span> <i class="icon fa-pencil fa-fw" aria-hidden="true"></i>
        // </a>
        // {% elseif T_THEME_NAME == 'basic' %}
    }/*}}}*/

    public function disable_signature($event)/*{{{*/
    {
        $t = $this->template;
        $pr = $event['post_row'];
        $gid = $this->poster_data['group_id'];
        $sql = 'SELECT snp_enable_signature from ' . GROUPS_TABLE .
            ' WHERE group_id =' . $gid;
        $result = $this->db->sql_query($sql, 30);
        $row = $this->db->sql_fetchrow($result);
        $bShowSignature = $row['snp_enable_signature'] ? true : false;
        $this->db->sql_freeresult($result);
        if (!$bShowSignature)
        {
            $pr['SIGNATURE'] = false;
            $event['post_row'] = $pr;
        }
    }/*}}}*/

    public function modify_signature($event)/*{{{*/
    {
        $return_url = '/ucp.php?i=ucp_profile&mode=signature';
        $gid = $this->user->data['group_id'];
        $sql = 'SELECT snp_signature_rows from ' . GROUPS_TABLE .
            ' WHERE group_id =' . $gid;
        $result = $this->db->sql_query($sql, 30);
        $row = $this->db->sql_fetchrow($result);
        $nSignatureRow = $row['snp_signature_rows'] or 0;
        $this->db->sql_freeresult($result);
        $signature = $event['signature'];
        preg_match('#\[[^]]*?hide[^]]*?\]#is', $signature, $match);
        // Delete signature if [hide] tag is used
        if ($match)
        {
            $event['signature'] = '';
        }
        else
        {
            $split = explode(PHP_EOL, $signature);
            $split = array_slice($split, 0, $nSignatureRow);
            $signature = implode(PHP_EOL, $split);
            $event['signature'] = $signature;
        }
    }/*}}}*/

    public function modify_signature_deprecated($event)/*{{{*/
    {
        // 'core.ucp_profile_modify_signature_sql_ary'   => 'modify_signature',
        $t = $this->template;
        $pr = $event['post_row'];
        $user_id = $this->user->data['user_id'];
        $gid = $this->user->data['group_id'];
        $sql = 'SELECT snp_signature_rows from ' . GROUPS_TABLE .
            ' WHERE group_id =' . $gid;
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $nSignatureRow = $row['snp_signature_rows'] or 0;
        $this->db->sql_freeresult($result);
        $sql_ary = $event['sql_ary'];
        $user_sig = &$sql_ary['user_sig'];
        $split = explode('\n', $user_sig);
        $split = array_slice($split, 0, $nSignatureRow);
        $user_sig = implode('\n', $split);
        $user_sig = closetags($user_sig);
        $event['sql_ary'] = $sql_ary;
    }/*}}}*/

    public function modify_avatar_thanks($event) /*{{{*/
    {
        $poster_id = $event['poster_id'];
        $sql = 'SELECT snp_disable_avatar_thanks_link FROM ' . USERS_TABLE . ' WHERE user_id=' . $poster_id;
        $result = $this->db->sql_query($sql, 30);
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
    }/*}}}*/

}
