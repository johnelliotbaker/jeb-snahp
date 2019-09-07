<?php
namespace jeb\snahp\controller;
use \Symfony\Component\HttpFoundation\Response;
use jeb\snahp\core\base;

class userscript extends base
{
    protected $prefix;

    public function __construct($prefix)
    {
        $this->prefix = $prefix;
    }

    public function handle($mode)
    {
        switch ($mode)
        {
        case 'bump_topic':
            $cfg['tpl_name'] = '';
            $cfg['base_url'] = '/app.php/snahp/userscript/bump_topic/';
            $cfg['title'] = 'Bump Topic';
            return $this->handle_bump_topic($cfg);
            break;
        case 'username':
            return $this->handle_username();
        case 'userid':
            return $this->handle_userid();
        default:
            break;
        }
        trigger_error('Nothing to see here. Move along.');
    }

    public function handle_userid()/*{{{*/
    {
        $partial = $this->request->variable('partial', '');
        $partial = utf8_clean_string($partial);
        $data = [];
        if ($partial and strlen($partial)>2)
        {
            $sql = 'SELECT user_id, username_clean FROM ' . USERS_TABLE . 
                " WHERE username_clean LIKE '$partial%'";
            $result = $this->db->sql_query_limit($sql, 10);
            $rowset = $this->db->sql_fetchrowset($result);
            $this->db->sql_freeresult($result);
            foreach($rowset as $row)
            {
                $tmp = [];
                $tmp['username'] = $row['username_clean'];
                $tmp['user_id'] = $row['user_id'];
                $data[] = $tmp;
            }
        }
        $js = new \phpbb\json_response();
        $js->send($data);
    }/*}}}*/

    public function handle_username()/*{{{*/
    {
        $partial = $this->request->variable('partial', '');
        $partial = utf8_clean_string($partial);
        $data = [];
        if ($partial and strlen($partial)>2)
        {
            $sql = 'SELECT username_clean FROM ' . USERS_TABLE . 
                " WHERE username_clean LIKE '$partial%'";
            $result = $this->db->sql_query_limit($sql, 10);
            $rowset = $this->db->sql_fetchrowset($result);
            $this->db->sql_freeresult($result);
            foreach($rowset as $row)
            {
                $data[] = $row['username_clean'];
            }
        }
        $js = new \phpbb\json_response();
        $js->send($data);
    }/*}}}*/

    public function get_or_reject_topic_data($tid)/*{{{*/
    {
        if (!$tid)
        {
            trigger_error('No topic_id was provided.');
        }
        $topicdata = $this->select_topic($tid);
        if (!$topicdata)
        {
            trigger_error('That topic does not exist.');
        }
        return $topicdata;
    }/*}}}*/

    public function enable_bump_topic($b_enable=true, $perm=[])/*{{{*/
    {
        $time = time();
        $tid = $this->request->variable('t', '');
        $def = $this->container->getParameter('jeb.snahp.bump_topic')['def'];
        $script_status = [];
        if (!$perm)
        {
            $perm = $this->get_bump_permission($tid);
        }
        $topic_data = $perm['topic_data'];
        if (!$topic_data) trigger_error("Topic $tid does not exist.");
        $forum_id = $topic_data['forum_id'];
        $fid_listings = $this->config['snp_fid_listings'];
        $a_fid = $this->select_subforum($fid_listings);
        if (!in_array($forum_id, $a_fid))
        {
            trigger_error('You can only bump topics in listings.');
        }
        $bump_data = $perm['bump_data'];
        if ($b_enable)
        {
            if ($perm['b_enable'])
            {
                if (!$bump_data)
                { // If no bump data, create bump
                    $this->create_bump_topic($topic_data);
                    $script_status[] = 'You can now start bumping this topic.';
                }
                else
                {
                    $data = [
                        'status' => $def['enable'],
                        'topic_time' => $time,
                        'n_bump' => 0,
                    ];
                    $this->update_bump_topic($tid, $data);
                    $script_status[] = 'Bumping has been enabled.';
                }
            }
            else
            {
                // Error if op is trying to enable a topic disabled by mod.
                trigger_error('You cannot enable bumping, please ask a moderator.');
            }
        }
        else
        {
            if ($perm['b_disable'])
            {
                $data = [
                    'status' => $def['disable'],
                    'topic_time' => 0,
                    'n_bump' => 0,
                ];
                $this->update_bump_topic($tid, $data);
                $script_status[] = 'Bumping has been disabled.';
            }
            else
            {
                trigger_error('You cannot disble bumping.');
            }
        }
        $strn = implode('<br>', $script_status);
        $return_url = '/viewtopic.php?t='.$tid;
        if (array_key_exists('forum_id', $topic_data))
        {
            $return_url .= '&f='.$topic_data['forum_id'];
        }
        meta_refresh(2, $return_url);
        trigger_error($strn);
    }/*}}}*/

    function phpbb_bump_topic($forum_id, $topic_id, $post_data, $bump_time = false)/*{{{*/
    {
        // From includes/functions_posting.php
        global $config, $db, $user, $phpEx, $phpbb_root_path, $phpbb_log;
        if ($bump_time === false)
        {
            $bump_time = time();
        }
        // Begin bumping
        $db->sql_transaction('begin');
        $sql = 'UPDATE ' . TOPICS_TABLE . "
            SET topic_last_post_time = $bump_time,
            topic_time = $bump_time,
            topic_bumped = 1,
            topic_bumper = " . $user->data['user_id'] . "
            WHERE topic_id = $topic_id";
        $db->sql_query($sql);
        $sql = 'UPDATE ' . FORUMS_TABLE . "
            SET forum_last_post_id = " . $post_data['topic_last_post_id'] . ",
            forum_last_poster_id = " . $post_data['topic_last_poster_id'] . ",
            forum_last_post_subject = '" . $db->sql_escape($post_data['topic_last_post_subject']) . "',
            forum_last_post_time = $bump_time,
            forum_last_poster_name = '" . $db->sql_escape($post_data['topic_last_poster_name']) . "',
            forum_last_poster_colour = '" . $db->sql_escape($post_data['topic_last_poster_colour']) . "'
            WHERE forum_id = $forum_id";
        $db->sql_query($sql);
        $db->sql_transaction('commit');
        markread('post', $forum_id, $topic_id, $bump_time);
        markread('topic', $forum_id, $topic_id, $bump_time);
        if ($config['load_db_lastread'] && $user->data['is_registered'])
        {
            $sql = 'SELECT mark_time
                FROM ' . FORUMS_TRACK_TABLE . '
                WHERE user_id = ' . $user->data['user_id'] . '
                AND forum_id = ' . $forum_id;
            $result = $db->sql_query($sql);
            $f_mark_time = (int) $db->sql_fetchfield('mark_time');
            $db->sql_freeresult($result);
        }
        else if ($config['load_anon_lastread'] || $user->data['is_registered'])
        {
            $f_mark_time = false;
        }
        if (($config['load_db_lastread'] && $user->data['is_registered']) || $config['load_anon_lastread'] || $user->data['is_registered'])
        {
            $sql = 'SELECT forum_last_post_time
                FROM ' . FORUMS_TABLE . '
                WHERE forum_id = ' . $forum_id;
            $result = $db->sql_query($sql);
            $forum_last_post_time = (int) $db->sql_fetchfield('forum_last_post_time');
            $db->sql_freeresult($result);
            update_forum_tracking_info($forum_id, $forum_last_post_time, $f_mark_time, false);
        }
    }/*}}}*/

    public function bump_topic()/*{{{*/
    {
        $tid = $this->request->variable('t', '');
        $perm = $this->get_bump_permission($tid);
        $b_bump = $perm['b_bump'];
        if (!$b_bump)
        {
            trigger_error('You don\'t have the permission to bump this topic.');
        }
        $b_mod = $this->is_dev();
        $group_id = $this->user->data['group_id'];
        $group_config = $this->select_bump_group_config($group_id);
        $cooldown = $group_config['snp_bump_cooldown'];
        $time = time();
        $script_status = [];
        $topic_data = $this->get_or_reject_topic_data($tid);
        $bump_data = $this->select_bump_topic($tid);
        if (!$bump_data)
        {
            $this->enable_bump_topic($b_enable=true, $perm=[]);
        }
        $n_bump = $bump_data['n_bump'];
        // cooldown rejection
        if (!$b_mod && $n_bump > 0 && $time < $bump_data['topic_time'] + $cooldown)
        {
            $return_url = '/viewtopic.php?t='.$tid;
            if (array_key_exists('forum_id', $topic_data))
            {
                $return_url .= '&f='.$topic_data['forum_id'];
            }
            meta_refresh(8, $return_url);
            $status = [
                'You cannot bump again until ' . $this->user->format_date($bump_data['topic_time'] + $cooldown) . '.',
                'You may ask a moderator to bump this topic for you.',
            ];
            trigger_error(implode('<br>', $status));
        }
        $time = time();
        $forum_id = $topic_data['forum_id'];
        $topic_id = $topic_data['topic_id'];
        $this->phpbb_bump_topic($forum_id, $topic_id, $topic_data);
        $bump_data = $this->select_bump_topic($tid);
        $data = [ 
            'topic_time' => $time,
            'n_bump' => $bump_data['n_bump'] + 1,
        ];
        $this->update_bump_topic($tid, $data);
        $title = $topic_data['topic_title'];
        $strn = "$title has been bumped on " . $this->user->format_date($time);
        $return_url = '/viewtopic.php?t='.$tid;
        if (array_key_exists('forum_id', $topic_data))
        {
            $return_url .= '&f='.$topic_data['forum_id'];
        }
        meta_refresh(2, $return_url);
        trigger_error($strn);
    }/*}}}*/

    public function handle_bump_topic($cfg)/*{{{*/
    {
        $this->reject_anon();
        $snp_bump_b_topic = $this->config['snp_bump_b_topic'];
        if (!$snp_bump_b_topic)
        {
            trigger_error('The administrator has disabled bumping sitewide.');
        }
        $action = $this->request->variable('action', '');
        if (!$action)
        {
            trigger_error('Must specify a valid action.');
        }
        switch ($action)
        {
        case 'enable':
            $this->enable_bump_topic(true);
            break;
        case 'disable':
            $this->enable_bump_topic(false);
            break;
        case 'bump':
            $this->bump_topic($cfg);
            break;
        default:
            trigger_error('Invalid action.');
        }
        $return_url = '/viewtopic.php?t='.$tid;
        if (array_key_exists('forum_id', $topic_data))
        {
            $return_url .= '&f='.$topic_data['forum_id'];
        }
        meta_refresh(2, $return_url);
        trigger_error($strn);
    }/*}}}*/

    public function handle_thanks_given($cfg)/*{{{*/
    {
        $this->reject_anon();
        $tpl_name = $cfg['tpl_name'];
        if ($tpl_name)
        {
            $base_url = $cfg['base_url'];
            $pagination = $this->container->get('pagination');
            $per_page = $this->config['posts_per_page'];
            $start = $this->request->variable('start', 0);
            [$data, $total] = $this->select_thanks_given($per_page, $start);
            $pagination->generate_template_pagination(
                $base_url, 'pagination', 'start', $total, $per_page, $start
            );
            foreach ($data as $row)
            {
                $tid = $row['topic_id'];
                $pid = $row['post_id'];
                $post_time = $this->user->format_date($row['post_time']);
                $u_details = "/viewtopic.php?t=$tid&p=$pid#p$pid";
                $poster_id = $row['poster_id'];
                $poster_name = $row['username'];
                $poster_colour = $row['user_colour'];
                $thanks_time = $this->user->format_date($row['thanks_time']);
                $group = array(
                    'FORUM_ID'       => $row['forum_id'],
                    'TOPIC_ID'       => $row['topic_id'],
                    'POST_SUBJECT'   => $row['post_subject'],
                    'POST_TIME'      => $post_time,
                    'POSTER_ID'      => $poster_id,
                    'POSTER_NAME'    => $poster_name,
                    'POSTER_COLOUR'  => $poster_colour,
                    'THANKS_TIME'    => $thanks_time,
                    'U_VIEW_DETAILS' => $u_details,
                );
                $this->template->assign_block_vars('postrow', $group);
            }
            $this->template->assign_var('TITLE', $cfg['title']);
            return $this->helper->render($tpl_name, $cfg['title']);
        }
    }/*}}}*/

    public function handle_favorite($cfg)/*{{{*/
    {
        $this->reject_anon();
        $tpl_name = $cfg['tpl_name'];
        if ($tpl_name)
        {
            $base_url = $cfg['base_url'];
            $fid_listings = $this->config['snp_fid_listings'];
            $pagination = $this->container->get('pagination');
            $per_page = $this->config['posts_per_page'];
            $start = $this->request->variable('start', 0);
            $sort_mode = $cfg['sort_mode'];
            [$data, $total] = $this->select_one_day($fid_listings, $per_page, $start, $sort_mode);
            $pagination->generate_template_pagination(
                $base_url, 'pagination', 'start', $total, $per_page, $start
            );
            foreach ($data as $row)
            {
                $tid = $row['topic_id'];
                $topic_time = $this->user->format_date($row['topic_time']);
                $u_details = '/viewtopic.php?t=' . $tid;
                $poster_id = $row['topic_poster'];
                $poster_name = $row['topic_first_poster_name'];
                $poster_colour = $row['topic_first_poster_colour'];
                $lp_id = $row['topic_last_poster_id'];
                $lp_name = $row['topic_last_poster_name'];
                $lp_colour = $row['topic_last_poster_colour'];
                $lp_subject = $row['topic_last_post_subject'];
                $lp_time = $this->user->format_date($row['topic_last_post_time']);
                $group = array(
                    'FORUM_ID'       => $row['forum_id'],
                    'TOPIC_ID'       => $row['topic_id'],
                    'TOPIC_TITLE'    => $row['topic_title'],
                    'TOPIC_TIME'     => $topic_time,
                    'POSTER_ID'      => $poster_id,
                    'POSTER_NAME'    => $poster_name,
                    'POSTER_COLOUR'  => $poster_colour,
                    'LP_ID'          => $lp_id,
                    'LP_NAME'        => $lp_name,
                    'LP_COLOUR'      => $lp_colour,
                    'LP_SUBJECT'     => $lp_subject,
                    'LP_TIME'        => $lp_time,
                    'TOPIC_VIEWS'    => $row['topic_views'],
                    'FORUM_NAME'     => $row['forum_name'],
                    'REPLIES'        => $row['topic_posts_approved'] - 1,
                    'U_VIEW_DETAILS' => $u_details,
                );
                $this->template->assign_block_vars('postrow', $group);
            }
            $this->template->assign_var('TITLE', $cfg['title']);
            return $this->helper->render($tpl_name, $cfg['title']);
        }
    }/*}}}*/

}
