<?php

namespace jeb\snahp\controller;

use \Symfony\Component\HttpFoundation\Response;
use jeb\snahp\core\base;



class favorite extends base
{
    protected $prefix;

    public function __construct($prefix)
    {
        $this->prefix = $prefix;
        $this->perPage = 30;
    }

    public function handle($mode)
    {
        $this->reject_anon();
        $this->reject_bots();
        switch ($mode) {
        case 'oneday':
            $cfg['tpl_name'] = '@jeb_snahp/favorite/favorite_oneday.html';
            $cfg['sort_mode'] = 'id';
            $cfg['base_url'] = '/app.php/snahp/favorite/oneday/';
            $cfg['title'] = 'New Listings';
            return $this->handle_favorite($cfg);
            break;
        case 'popular':
            $cfg['tpl_name'] = '@jeb_snahp/favorite/favorite_oneday.html';
            $cfg['sort_mode'] = 'views';
            $cfg['base_url'] = '/app.php/snahp/favorite/popular/';
            $cfg['title'] = 'Popular Listings';
            return $this->handle_favorite($cfg);
            break;
        case 'replies':
            $cfg['tpl_name'] = '@jeb_snahp/favorite/favorite_oneday.html';
            $cfg['sort_mode'] = 'replies';
            $cfg['base_url'] = '/app.php/snahp/favorite/replies/';
            $cfg['title'] = 'Most Discussed';
            return $this->handle_favorite($cfg);
            break;
        case 'thanks_given':
            $cfg['tpl_name'] = '@jeb_snahp/favorite/thanks_given.html';
            $cfg['base_url'] = '/app.php/snahp/favorite/thanks_given/';
            $cfg['title'] = 'Thanks Given';
            return $this->handle_thanks_given($cfg);
            break;
        case 'open_requests':
            $cfg['tpl_name'] = '@jeb_snahp/favorite/open_requests.html';
            $cfg['base_url'] = '/app.php/snahp/favorite/open_requests/';
            $cfg['title'] = 'Open Requests';
            return $this->handle_open_requests($cfg);
            break;
        case 'accepted_requests':
            $cfg['tpl_name'] = '@jeb_snahp/favorite/accepted_requests.html';
            $cfg['base_url'] = '/app.php/snahp/favorite/accepted_requests/';
            $cfg['title'] = 'Accepted Requests';
            return $this->handle_accepted_requests($cfg);
            break;
        default:
            break;
        }
        trigger_error('showing favorite.');
    }

    public function handle_accepted_requests($cfg)
    {
        $tpl_name = $cfg['tpl_name'];
        if ($tpl_name) {
            $type = $this->request->variable('type', 'all');
            $base_url = $cfg['base_url'];
            $pagination = $this->container->get('pagination');
            $start = $this->request->variable('start', 0);
            if ($type=='dib') {
                $cfg['title'] = 'Accepted Requests (Dibs)';
                [$data, $total] = $this->select_accepted_requests($this->perPage, $start, 'dib');
                $base_url .= "?type=dib";
            } else {
                [$data, $total] = $this->select_accepted_requests($this->perPage, $start);
            }
            $pagination->generate_template_pagination(
                $base_url,
                'pagination',
                'start',
                $total,
                $this->perPage,
                $start
            );
            foreach ($data as $row) {
                $tid = $row['tid'];
                $pid = $row['pid'];
                $created_time = $this->user->format_date($row['created_time']);
                $u_details = "/viewtopic.php?t=$tid&p=$pid#p$pid";
                $group = array(
                    'TOPIC_TITLE'    => $row['topic_title'],
                    'CREATED_TIME'   => $created_time,
                    'U_VIEW_DETAILS' => $u_details,
                );
                $this->template->assign_block_vars('postrow', $group);
            }
            $this->template->assign_var('TITLE', $cfg['title']);
            return $this->helper->render($tpl_name, $cfg['title']);
        }
    }

    public function handle_open_requests($cfg)
    {
        $tpl_name = $cfg['tpl_name'];
        if ($tpl_name) {
            $type = $this->request->variable('type', 'all');
            $base_url = $cfg['base_url'];
            $pagination = $this->container->get('pagination');
            $start = $this->request->variable('start', 0);
            if ($type=='fulfill') {
                $cfg['title'] = 'Fulfilled Requests';
                [$data, $total] = $this->select_fulfilled_requests($this->perPage, $start);
                $base_url .= "?type=fulfill";
            } else {
                [$data, $total] = $this->select_open_requests($this->perPage, $start);
            }
            $pagination->generate_template_pagination(
                $base_url,
                'pagination',
                'start',
                $total,
                $this->perPage,
                $start
            );
            foreach ($data as $row) {
                $tid = $row['tid'];
                $pid = $row['pid'];
                $created_time = $this->user->format_date($row['created_time']);
                $u_details = "/viewtopic.php?t=$tid&p=$pid#p$pid";
                $group = array(
                    'TOPIC_TITLE'    => $row['topic_title'],
                    'CREATED_TIME'   => $created_time,
                    'U_VIEW_DETAILS' => $u_details,
                );
                $this->template->assign_block_vars('postrow', $group);
            }
            $this->template->assign_var('TITLE', $cfg['title']);
            return $this->helper->render($tpl_name, $cfg['title']);
        }
    }

    public function handle_thanks_given($cfg)
    {
        $tpl_name = $cfg['tpl_name'];
        if ($tpl_name) {
            $base_url = $cfg['base_url'];
            $pagination = $this->container->get('pagination');
            $start = $this->request->variable('start', 0);
            [$data, $total] = $this->select_thanks_given($this->perPage, $start);
            $pagination->generate_template_pagination(
                $base_url,
                'pagination',
                'start',
                $total,
                $this->perPage,
                $start
            );
            foreach ($data as $row) {
                $tid = $row['topic_id'];
                $pid = $row['post_id'];
                $post_time = $this->user->format_date($row['post_time']);
                $u_details = "/viewtopic.php?t=$tid&p=$pid#p$pid";
                $poster_id = $row['poster_id'];
                $poster_name = $row['username'];
                $poster_colour = $row['user_colour'];
                $thanks_time = $this->user->format_date($row['thanks_time']);
                // $post_subject = $this->add_host_icon($row['post_subject']);
                $post_subject = $this->encodeTags($row['post_subject']);
                $group = array(
                    'FORUM_ID'       => $row['forum_id'],
                    'TOPIC_ID'       => $row['topic_id'],
                    'POST_SUBJECT'   => $post_subject,
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
    }

    public function handle_favorite($cfg)
    {
        $cooldown = 20; // Sql query cache cooldown
        $tpl_name = $cfg['tpl_name'];
        if ($tpl_name) {
            // FAVORITE FILTER START //
            $b_dev = $this->is_dev_server();
            if ($b_dev) {
                $a_img_url = $this->container->getParameter('jeb.snahp.fav')['dev']['img'];
            } else {
                $a_img_url = $this->container->getParameter('jeb.snahp.fav')['production']['img'];
            }
            $fid_listings = $this->config['snp_fid_listings'];
            // Process submitted form
            if ($this->request->is_set_post('submit')) {
                if (!check_form_key('jeb_snp')) {
                    trigger_error('FORM_INVALID', E_USER_WARNING);
                }
                $a_target_forum_full_depth = $this->select_subforum($fid_listings, $cooldown);
                $exclude = [];
                foreach ($a_target_forum_full_depth as $fid) {
                    $b_exclude = !$this->request->variable('fav_filter_fid_' . $fid, false);
                    if ($b_exclude) {
                        $exclude[] = $fid;
                    }
                }
                $exclude = serialize($exclude);
                $data = [ 'snp_fav_fid_exclude' => $exclude, ];
                $this->update_user($this->user->data['user_id'], $data);
                meta_refresh(2.5, $cfg['base_url']);
                trigger_error('Setting your filter preferences ...');
            }
            $a_forum = [];
            $a_listings_forum_parent = $this->select_subforum_with_name($fid_listings, $cooldown, true);
            $exclude = unserialize($this->user->data['snp_fav_fid_exclude']);
            $exclude = is_array($exclude) ? $exclude : [];
            $image_lookup = []; // Piggy back this loop to fil image urls
            foreach ($a_listings_forum_parent as $forum) {
                // Iterate through parents and get the children
                $parent_id = $forum['forum_id'];
                $rowset = $this->select_subforum_with_name($parent_id, $cooldown);
                if ($rowset) {
                    $a_forum[$parent_id]['children'] = [];
                    foreach ($rowset as $row) {
                        $forum_id = $row['forum_id'];
                        if (array_key_exists($forum_id, $a_img_url)) {
                            $image_lookup[$forum_id] = $a_img_url[$forum_id];
                        } elseif (array_key_exists($parent_id, $a_img_url)) {
                            $image_lookup[$forum_id] = $a_img_url[$parent_id];
                        } else {
                            $image_lookup[$forum_id] = '';
                        }
                        $a_forum[$parent_id]['children'][] = [
                            'ID' => $forum_id,
                            'NAME' => $row['forum_name'],
                            'IMG' => $image_lookup[$forum_id],
                            'CHECK' => in_array($forum_id, $exclude),
                        ];
                    }
                }
            }
            foreach ($a_forum as $key=>$forum) {
                $this->template->assign_block_vars('forum', []);
                foreach ($a_forum[$key]['children'] as $child) {
                    $this->template->assign_block_vars('forum.subforum', $child);
                }
            }
            // FAVORITE FILTER END //
            // FAVORITE CONTENT START //
            $base_url = $cfg['base_url'];
            $fid_listings = $this->config['snp_fid_listings'];
            $pagination = $this->container->get('pagination');
            $start = $this->request->variable('start', 0);
            $sort_mode = $cfg['sort_mode'];
            [$data, $total] = $this->select_one_day($fid_listings, $this->perPage, $start, $sort_mode, $exclude, $cooldown);
            $pagination->generate_template_pagination(
                $base_url,
                'pagination',
                'start',
                $total,
                $this->perPage,
                $start
            );
            foreach ($data as $row) {
                $forum_id = $row['forum_id'];
                $img_url = $image_lookup[$forum_id];
                $tid = $row['topic_id'];
                $topic_time = $this->user->format_date($row['topic_time']);
                // $topic_title = $this->add_host_icon($row['topic_title']);
                $topic_title = $this->encodeTags($row['topic_title']);
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
                    'TOPIC_TITLE'    => $topic_title,
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
                    'U_IMG_URL'      => $img_url,
                    'U_VIEW_DETAILS' => $u_details,
                );
                $this->template->assign_block_vars('postrow', $group);
            }
            $this->template->assign_vars([
                'TITLE' => $cfg['title'],
            ]);
            add_form_key('jeb_snp');
            return $this->helper->render($tpl_name, $cfg['title']);
        }
    }
}
