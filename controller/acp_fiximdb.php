<?php
namespace jeb\snahp\controller;

use jeb\snahp\core\base;
use \Symfony\Component\HttpFoundation\Response;
use \Symfony\Component\HttpFoundation\JsonResponse;

function prn($var) {
    if (is_array($var))
    { foreach ($var as $k => $v) { echo "... $k => "; prn($v); }
    } else { echo "$var<br>"; }
}

class acp_fiximdb extends base
{
    protected $table_prefix;
    public function __construct($table_prefix)
    {
        $this->table_prefix = $table_prefix;
    }

    public function handle($mode)
    {
        $this->reject_non_admin('Error Code: 9e8f5608d8');
        switch ($mode)
        {
        case 'test':
            $cfg = [];
            return $this->test($cfg);
        case 'modify_imdb':
            $cfg = [];
            return $this->modify_imdb($cfg);
        default:
            break;
        }
        $this->reject_non_admin('Error Code: e6ff7c2e73');
        trigger_error('You must specify valid mode.');
    }

    public function handle_giveaways($cfg)
    {
        $this->reject_anon();
        $tpl_name = $cfg['tpl_name'];
        if ($tpl_name)
        {
            $rowset = $this->select_groups();
            foreach ($rowset as $row) {
                $this->template->assign_block_vars('A', $row);
            }
            return $this->helper->render($tpl_name, $cfg['title']);
        }
    }

    public function modify_imdb($cfg)
    {
        global $phpbb_root_path, $phpEx, $auth, $config, $db, $user, $phpbb_dispatcher;
        $error = false;
        $search = new \phpbb\search\fulltext_native($error, $phpbb_root_path, $phpEx, $auth, $config, $db, $user, $phpbb_dispatcher);
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        $tbl = $this->container->getParameter('jeb.snahp.tables');
        $forum_id = (int) $this->request->variable('f', '0');
        if (!$forum_id)
        {
            $this->send_message(['status'=>'ERROR']);
            $js = new \phpbb\json_response();
            return $js->send([]);
        }
        $a_sub = $this->select_subforum($forum_id);
        $where = $this->db->sql_in_set('forum_id', $a_sub);
        $sql = 'SELECT COUNT(*) as total FROM ' . TOPICS_TABLE . " WHERE $where";
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        $total = $row['total'];
        $sql = 'SELECT * FROM ' . TOPICS_TABLE . " WHERE $where";
        $i = 0;
        $this->send_message(['status'=>'START', 'i' => $i, 'total' => $total]);
        try
        {
            $result_loop = $this->db->sql_query($sql);
            while($row = $this->db->sql_fetchrow($result_loop))
            {
                $i += 1;
                $post_id = $row['topic_first_post_id'];
                if (!$post_id) continue;
                $sql = 'SELECT post_text, poster_id, forum_id, post_id, post_subject FROM ' . POSTS_TABLE . " WHERE post_id={$post_id}";
                $this->db->sql_query($sql);
                $result = $this->db->sql_query($sql);
                $row = $this->db->sql_fetchrow($result);
                $this->db->sql_freeresult($result);
                $text = $row['post_text'];
                // Modify the imdb
                // generate_text_for_display($text, '0', '0', 0);
                $ptn = '/tt(\d{7})]<\/s>IMDb<e>/';
                $b_match = preg_match($ptn, $text, $match);
                if (!$b_match) continue;
                $imdb_id = $match[1];
                $text = preg_replace($ptn, 'tt$1]</s>IMDb tt$1<e>', $text);
                $data['post_text'] = $text;
                $sql = 'UPDATE ' . POSTS_TABLE . '
                    SET ' . $this->db->sql_build_array('UPDATE', $data) .'
                    WHERE post_id=' . $post_id;
                $this->db->sql_query($sql);
                $search->index_remove([$row['post_id']], [$row['poster_id']], [$row['forum_id']]);
                $mode = 'edit_first_post';
                $subject = $row['post_subject'] . " tt{$imdb_id}";
                $search->index($mode, $row['post_id'], $text, $subject, $row['poster_id'], $row['forum_id']);
                if ($i % 100 == 0)
                {
                    $this->send_message(['status' => 'PROGRESS', 'i' => $i, 'total' => $total]);
                }
            }
            $this->db->sql_freeresult($result_loop);
        }
        catch (\Exception $e)
        {
            $this->send_message(['status'=>'ERROR', 'i' => $total, 'total' => $total]);
        }
        $this->send_message(['status'=>'COMPLETE', 'i' => $total, 'total' => $total]);
        $js = new \phpbb\json_response();
        $js->send([]);
    }

    public function test($mode)
    {
        $data = [];
        $js = new \phpbb\json_response();
        $js->send($data);
    }

    public function send_message($data) {
        echo "data: " . json_encode($data) . PHP_EOL;
        echo PHP_EOL;
        ob_flush();
        flush();
    }

}
