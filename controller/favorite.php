<?php
namespace jeb\snahp\controller;
use \Symfony\Component\HttpFoundation\Response;
use jeb\snahp\core\base;

function prn($var) {
    if (is_array($var))
    { foreach ($var as $k => $v) { echo "... $k => "; prn($v); }
    } else { echo "$var<br>"; }
}

class favorite extends base
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
            $cfg['base_url'] = '/app.php/snahp/favorite/thanks_given/';
            $cfg['title'] = 'Open Requests';
            return $this->handle_open_requests($cfg);
            break;
        default:
            break;
        }
        trigger_error('showing favorite.');
    }

    public function handle_open_requests($cfg)
    {
        $this->reject_anon();
        $tpl_name = $cfg['tpl_name'];
        if ($tpl_name)
        {
            $base_url = $cfg['base_url'];
            $pagination = $this->container->get('pagination');
            $per_page = $this->config['posts_per_page'];
            $start = $this->request->variable('start', 0);
            [$data, $total] = $this->select_open_requests($per_page, $start);
            $pagination->generate_template_pagination(
                $base_url, 'pagination', 'start', $total, $per_page, $start
            );
            foreach ($data as $row)
            {
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
    }

    public function handle_favorite($cfg)
    {
        $this->reject_anon();
        $tpl_name = $cfg['tpl_name'];
        if ($tpl_name)
        {
            // Temporarily hard code some forums
            // TODO: Find a better way to get these
            $a_img_url = [
                'imported' => 'https://i.imgur.com/9RLP1KV.png',
                'apps' => 'https://i.imgur.com/7hjHY25.png',
                'games' => 'https://i.imgur.com/YBPkBaX.png',
                'tv' => 'https://i.imgur.com/i9pIvNy.png',
                'movies' => 'https://i.imgur.com/pK0D79T.png',
                'music' => 'https://i.imgur.com/g5r9eAI.png',
                'anime' => 'https://i.imgur.com/BpFV059.png',
                'misc' => 'https://i.imgur.com/X4OIR7c.png',
                'dev' => 'https://i.imgur.com/OU5XZDq.png',
            ];
            $a_category = [
                // Test Server
                // 'imported' => 50, 'apps' => 51, 'games' => 52,
                // 'movies' => 82, 'tv' => 53, 'music' => 54,
                // 'anime' => 55, 'misc' => 56, 'dev' => 57,
                // PRODUCTION Server
                'imported' => 27, 'apps' => 9, 'games' => 10,
                'movies' => 11, 'tv' => 12, 'music' => 14,
                'anime' => 13, 'misc' => 15, 'dev' => 76,
            ];
            $fid_lookup = [];
            foreach ($a_category as $name => $fid)
            {
                $a_fid = $this->select_subforum($fid, 3600);
                foreach ($a_fid as $f)
                {
                    $fid_lookup[$f] = $a_img_url[$name];
                }
            }

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
                $forum_id = $row['forum_id'];
                if (isset($fid_lookup[$forum_id]))
                {
                    $img_url = $fid_lookup[$forum_id];
                }
                else
                {
                    $img_url = '';
                }
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
                    'U_IMG_URL'      => $img_url,
                    'U_VIEW_DETAILS' => $u_details,
                );
                $this->template->assign_block_vars('postrow', $group);
            }
            $this->template->assign_vars([
                'TITLE' => $cfg['title'],
            ]);
            return $this->helper->render($tpl_name, $cfg['title']);
        }
    }

}
