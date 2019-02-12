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
class main_listener extends core implements EventSubscriberInterface
{
    protected $auth;
    protected $request;
    protected $config;
    protected $db;
    protected $template;
    protected $table_prefix;
    protected $notification;
    protected $sql_limit;
    protected $notification_limit;
    protected $at_prefix;

    public function __construct(
        auth $auth,
        request_interface $request,
        config $config,
        driver_interface $db,
        template $template,
        $table_prefix,
        manager $notification
    )
    {

        $this->auth               = $auth;
        $this->request            = $request;
        $this->config             = $config;
        $this->db                 = $db;
        $this->template           = $template;
        $this->table_prefix       = $table_prefix;
        $this->notification       = $notification;
        $this->sql_limit          = 10;
        $this->notification_limit = 10;
        $this->at_prefix          = '@@';
    }

    static public function getSubscribedEvents()
    {
        return array(
            'core.user_setup' => [
                ['include_donation_navlink', 0],
                ['include_quick_link', 0],
            ],
            'gfksx.thanksforposts.output_thanks_before'   => 'modify_avatar_thanks',
            'core.ucp_profile_modify_signature_sql_ary'   => 'modify_signature',
            'core.modify_posting_parameters'              => 'include_assets_before_posting',
            'core.viewtopic_modify_post_row'              => 'disable_signature',
            'core.notification_manager_add_notifications' => 'notify_op_on_report',
            'core.modify_submit_post_data'                => 'modify_quickreply_signature',
            'core.posting_modify_submit_post_after'       => array(
                array('notify_on_poke', 0),
            ),
            'core.posting_modify_message_text'            => 'colorize_at',
            'core.viewtopic_assign_template_vars_before'  => array(
                array('insert_new_topic_button',0),
            ),
        );
    }

    public function modify_quickreply_signature($event)
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
    }

    public function include_quick_link($event)
    {
        $this->template->assign_vars([
            'S_LOAD_UNREADS' => False,
            'B_SHOW_QL_REPLIES' => $this->config['snp_ql_fav_b_replies'],
            'B_SHOW_QL_VIEWS' => $this->config['snp_ql_fav_b_views'],
            'B_SHOW_QL_TIME' => $this->config['snp_ql_fav_b_time'],
            'B_SHOW_THANKS_GIVEN' => $this->config['snp_ql_thanks_given'],
            'B_SHOW_BOOKMARK' => $this->config['snp_ql_ucp_bookmark'],
            'B_SHOW_OPEN_REQUESTS' => $this->config['snp_ql_req_open_requests'],
        ]);
    }

    public function include_donation_navlink($event)
    {
        $this->template->assign_vars([
            'B_SHOW_DONATION_NAVLINK' => $this->config['snp_don_b_show_navlink'],
            'DON_URL' => $this->config['snp_don_url'],
        ]);
    }

    public function get_user_string_from_usernames_sql($aUserdata, $prepend='', $bDullBlocked=false)
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
    }

    public function get_user_data($aUsername)
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
    }

    public function colorize_at($event)
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
    }

    public function notify_on_poke($event)
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
    }

    public function notify_op_on_report($event)
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
    }

    public function include_assets_before_posting($event)
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
    }

    public function insert_new_topic_button($event)
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
        $sql = 'SELECT snp_enable_signature from ' . GROUPS_TABLE .
            ' WHERE group_id =' . $gid;
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
