<?php
namespace jeb\snahp\controller;
use \Symfony\Component\HttpFoundation\Response;
use \Symfony\Component\HttpFoundation\JsonResponse;
use jeb\snahp\core\base;

class thanks extends base
{

    public function __construct(
    )
    {
        // $this->u_action = 'app.php/snahp/admin/';
    }

    public function handle($mode)
    {
        switch($mode)
        {
        case 'datn':
            $cfg = [];
            return $this->delete_all_thanks_notifications($cfg);
        case 'thanks_given':
            $this->reject_non_moderator('Error Code: f8b2eb3b1e');
            $cfg['tpl_name'] = '@jeb_snahp/favorite/thanks_given.html';
            $cfg['base_url'] = '/app.php/snahp/thanks/handle/thanks_given/';
            $cfg['title'] = 'Thanks Given';
            return $this->handle_thanks_given($cfg);

        default:
            break;
        }
        trigger_error('You must provide a valid mode.');
    }

    public function handle_thanks_given($cfg)
    {
        $user_id = (int) $this->request->variable('user_id', '0');
        if (!$user_id)
        {
            trigger_error('You must provide a valid user_id. Error Code: c519466f1a');
        }
        $tpl_name = $cfg['tpl_name'];
        $base_url = $cfg['base_url'];
        $url = $base_url . "?user_id=$user_id";
        $pagination = $this->container->get('pagination');
        $per_page = $this->config['posts_per_page'];
        $start = $this->request->variable('start', 0);
        [$data, $total] = $this->select_thanks_given($per_page, $start, $user_id);
        $pagination->generate_template_pagination(
            $url, 'pagination', 'start', $total, $per_page, $start
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

    public function delete_all_thanks_notifications($cfg)
    {
        $this->reject_anon();
        $this->delete_thanks_notifications();
        $js = new JsonResponse(['status' => 'success']);
        return $js;
    }

}
