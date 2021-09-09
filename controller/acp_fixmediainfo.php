<?php
namespace jeb\snahp\controller;

use jeb\snahp\core\base;
use \Symfony\Component\HttpFoundation\Response;
use \Symfony\Component\HttpFoundation\JsonResponse;

class acp_fixmediainfo extends base
{
    protected $table_prefix;

    public function __construct($table_prefix)/*{{{*/
    {
        $this->table_prefix = $table_prefix;
    }

    public function handle($mode)/*{{{*/
    {
        $this->reject_non_admin('Error Code: e7fbfe4e4c');
        switch ($mode) {
        case 'test':
            $cfg = [];
            return $this->test($cfg);
        case 'modify_mediainfo':
            $cfg = [];
            return $this->modify_mediainfo($cfg);
        default:
            break;
        }
        trigger_error('You must specify valid mode.');
    }

    private function text_contains_mediainfo($text)/*{{{*/
    {
        $b_general = preg_match('#General\n#is', $text, $m);
        $b_video = preg_match('#Video\n#is', $text, $m);
        $b_audio = preg_match('#Audio(\s\#\d)?\n#is', $text, $m);
        return $b_general && $b_video && $b_audio;
    }

    private function mediainfo_tag_exists($text)/*{{{*/
    {
        return preg_match('#\[mediainfo\]#i', $text, $match);
    }

    private function get_first_codebox_content($text)/*{{{*/
    {
        $ptn_edit = '#\[code\](.*?)\[\/code\]#is';
        $b_match = preg_match($ptn_edit, $text, $match);
        return $b_match ? trim($match[1]) : null;
    }

    private function replace_codebox_with_mediainfo($text)/*{{{*/
    {
        $ptn_storage = '#<CODE><s>\[code\]</s>(.*?)<e>\[\/code\]</e></CODE>#is';
        return preg_replace($ptn_storage, '<MEDIAINFO><s>[mediainfo]</s>$1<e>[/mediainfo]</e></MEDIAINFO>', $text, 1);
    }

    public function modify_mediainfo($cfg)/*{{{*/
    {
        global $phpbb_root_path, $phpEx, $auth, $config, $db, $user, $phpbb_dispatcher;
        $error = false;
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        $tbl = $this->container->getParameter('jeb.snahp.tables');
        $forum_id = (int) $this->request->variable('f', '0');
        if (!$forum_id) {
            $this->send_message(['status'=>'ERROR: Must provide forum id']);
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
        $sql = 'SELECT * FROM ' . TOPICS_TABLE . " WHERE $where order by topic_id desc";
        $i = 0;
        $this->send_message(['status'=>'START', 'i' => $i, 'total' => $total]);
        try {
            $result_loop = $this->db->sql_query($sql);
            $start_time = microtime(true);
            while ($row = $this->db->sql_fetchrow($result_loop)) {
                $i += 1;
                if ($i % 100 == 0) {
                    $elapesed_time = microtime(true) - $start_time;
                    $this->send_message(['time' => $elapesed_time, 'status' => 'PROGRESS', 'i' => $i, 'total' => $total]);
                }
                $post_id = (int) $row['topic_first_post_id'];
                if (!$post_id) {
                    continue;
                }
                $sql = 'SELECT post_text, poster_id, forum_id, post_id, post_subject FROM ' . POSTS_TABLE . " WHERE post_id={$post_id}";
                $this->db->sql_query($sql);
                $result = $this->db->sql_query($sql);
                $row = $this->db->sql_fetchrow($result);
                $this->db->sql_freeresult($result);
                $text = $row['post_text'];
                $edit = generate_text_for_edit($text, 0, 0)['text'];
                if ($this->mediainfo_tag_exists($edit)) {
                    continue;
                }
                $content = $this->get_first_codebox_content($edit);
                if (!$content) {
                    continue;
                }
                if (!$this->text_contains_mediainfo($content)) {
                    continue;
                }
                $text = $this->replace_codebox_with_mediainfo($text);
                $data['post_text'] = $text;
                $this->update_post($post_id, $data);
            }
            $this->db->sql_freeresult($result_loop);
        } catch (\Exception $e) {
            $this->send_message(['status'=>'ERROR', 'i' => $total, 'total' => $total]);
        }
        $this->send_message(['status'=>'COMPLETE', 'i' => $total, 'total' => $total]);
        $js = new \phpbb\json_response();
        $js->send([]);
    }

    public function test($mode)/*{{{*/
    {
        $data = [];
        $js = new \phpbb\json_response();
        $js->send($data);
    }

    public function send_message($data)
    {/*{{{*/
        echo "data: " . json_encode($data) . PHP_EOL;
        echo PHP_EOL;
        ob_flush();
        flush();
    }
}
