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
use jeb\snahp\core\base;
use phpbb\auth\auth;
use phpbb\template\template;
use phpbb\config\config;
use phpbb\request\request_interface;
use phpbb\db\driver\driver_interface;
use phpbb\notification\manager;
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
class main_listener extends base implements EventSubscriberInterface
{
    protected $table_prefix;
    public function __construct($table_prefix)
    {
        $this->table_prefix = $table_prefix;
        $this->sql_limit          = 10;
        $this->notification_limit = 10;
        $this->at_prefix          = '@@';
    }

    static public function getSubscribedEvents()
    {
        return [
            'core.user_setup'                             => [
                ['test', 0],
                ['include_donation_navlink', 0],
                ['include_quick_link', 0],
                ['setup_custom_css', 0],
            ],
            'gfksx.thanksforposts.output_thanks_before'   => 'modify_avatar_thanks',
            'gfksx.thanksforposts.insert_thanks_before'   => 'insert_thanks',
            'core.display_forums_after'                   => 'show_thanks_top_list',
            'core.ucp_profile_modify_signature_sql_ary'   => 'modify_signature',
            'core.modify_posting_parameters'              => 'include_assets_before_posting',
            'core.viewtopic_modify_post_row'              => [
                ['easter_cluck', 1],
                ['block_zebra_foe_topicview', 1],
                ['show_requests_solved_avatar', 1],
                ['show_thanks_avatar', 1],
                ['show_bump_button', 1],
                ['disable_signature', 1],
                ['process_curly_tags', 2],
            ],
            'core.notification_manager_add_notifications' => 'notify_op_on_report',
            'core.modify_submit_post_data'                => 'modify_quickreply_signature',
            'core.posting_modify_submit_post_after'       => [
                ['notify_on_poke', 0],
            ],
            'core.posting_modify_message_text'            => 'colorize_at',
            'core.viewtopic_assign_template_vars_before'  => [
                ['insert_new_topic_button',0],
            ],
        ];
    }

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
            $post_row = $event['post_row'];/*{{{*/
            $username = '<span style="color:#'. $topic_first_poster_colour .'">' . $topic_first_poster_name . '</span>';
            $post_row['MESSAGE'] = '<b>' . $username . ' has blocked you from viewing this post.</b>';
            $event['post_row'] = $post_row;
        }
    }/*}}}*/

    public function test($event)/*{{{*/
    {
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
        $poster_id = $post_row['POSTER_ID'];
        $sql = 'SELECT snp_thanks_n_given, snp_thanks_n_received from '. USERS_TABLE . '
            WHERE user_id='. $poster_id;
        $result = $this->db->sql_query($sql, 1);
        $user_data = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        // $user_data = $this->select_user($poster_id);
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
        $poster_id = $post_row['POSTER_ID'];
        $sql = 'SELECT snp_req_n_solve from '. USERS_TABLE . '
            WHERE user_id='. $poster_id;
        $result = $this->db->sql_query($sql, 1);
        $user_data = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        // $user_data = $this->select_user($poster_id);
        $requests_solve = $user_data['snp_req_n_solve'];
        $post_row['REQUESTS_SOLVED'] = $requests_solve;
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

    public function process_curly_tags($event)/*{{{*/
    {
        $post_row = $event['post_row'];
        $message = &$post_row['MESSAGE'];
        $message = $this->interpolate_curly_tags($message);
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
            $a = '#(?<!])'. $at_prefix . $username_in_msg . '#is';
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
            $this->notification->add_notifications(array(
                'jeb.snahp.notification.type.basic',
            ), $data);
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
        $tid          = $data['topic_id'];
        $fid          = $data['forum_id'];
        $pid          = $data['post_id'];
        $topic_poster = $data['poster_id'];
        $notification_type_name = $event['notification_type_name'];
        switch($notification_type_name)
        {
        case 'notification.type.report_post':
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
        $forum_id = $this->request->variable('f', '');
        $topic_id = $this->request->variable('t', '');
        $pg_names = ['anime', 'listing', 'book', 'game'];
        if ($forum_id && is_numeric($forum_id) && !($topic_id))
        {
            foreach ($pg_names as $pg_name)
            {
                $fid_allowed[$pg_name] = explode(',', $this->config['snp_pg_fid_' . $pg_name]);
            }
            $user_id = $this->user->data['user_id'];
            $gid = $this->user->data['group_id'];
            $sql = 'SELECT snp_imdb_enable, snp_anilist_enable,
                    snp_googlebooks_enable, snp_gamespot_enable 
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
        $poster_id = $pr['POSTER_ID'];
        $sql = 'SELECT group_id from ' . USERS_TABLE .
            ' WHERE user_id=' . $poster_id;
        $result = $this->db->sql_query($sql, 30);
        $row = $this->db->sql_fetchrow($result);
        $gid = $row['group_id'];
        $this->db->sql_freeresult($result);
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
