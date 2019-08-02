<?php 
namespace jeb\snahp\controller;

use jeb\snahp\core\base;
use Symfony\Component\HttpFoundation\Response;
use \Symfony\Component\HttpFoundation\JsonResponse; 

class search_util extends base
{

    protected $table_prefix;

    public function __construct($table_prefix)/*{{{*/
    {
        $this->table_prefix = $table_prefix;
        // If a text in the message is inside [{$this->STBB}] mark it as title
        $this->STBB = 'st'; // [st]
    }/*}}}*/

	public function handle($mode)/*{{{*/
    {
        include_once('ext/jeb/snahp/core/functions_utility.php');
        include_once('includes/functions_content.php');
        if (!$this->config['snp_inv_b_master'])
        {
            trigger_error('Custom search system is disabled by the administrator. Error Code: ce6517a9ac');
        }
        $this->reject_anon();
        switch ($mode)
        {
            case 'index_topic': // To solve request
                $cfg = [];
                $this->index_topic($cfg);
                break;
            case 'handle_common_words':
                $this->reject_non_dev();
                $cfg['tpl_name'] = '@jeb_snahp/search_util/component/handle_common_words/base.html';
                $cfg['base_url'] = '/app.php/snahp/search_util/handle_common_words/';
                $cfg['title'] = 'Add or Remove Excluded Search Terms';
                $cfg['b_feedback'] = false;
                return $this->handle_common_words($cfg);
                break;
            case 'handle_mysql_search':
                $this->reject_non_dev();
                $cfg['tpl_name'] = '@jeb_snahp/search_util/component/handle_mysql_search/base.html';
                $cfg['base_url'] = '/app.php/snahp/search_util/handle_mysql_search/';
                $cfg['title'] = 'Power Search';
                $cfg['b_feedback'] = false;
                return $this->handle_mysql_search($cfg);
                break;
            default:
                break;
        }
        trigger_error('Error Code: 1fdd2c2b80');
    }/*}}}*/

    private function remove_common_word($word)/*{{{*/
    {
        $sql = 'INSERT INTO ' . SEARCH_WORDLIST_TABLE . '(word_text, word_common, word_count)
            VALUES ' . "('{$word}', 0, 0) " . '
            ON DUPLICATE KEY UPDATE
                word_common=0';
        $this->db->sql_query($sql);
    }/*}}}*/

    private function add_common_word($word)/*{{{*/
    {
        $sql = 'INSERT INTO ' . SEARCH_WORDLIST_TABLE . '(word_text, word_common, word_count)
            VALUES ' . "('{$word}', 1, 0) " . '
            ON DUPLICATE KEY UPDATE
                word_common=1';
        $this->db->sql_query($sql);
    }/*}}}*/

    private function handle_common_words($cfg)/*{{{*/
    {
        $tpl_name = $cfg['tpl_name'];
        if ($tpl_name)
        {
            add_form_key('jeb_snp');
            // IF SUBMITTED
            if ($this->request->is_set_post('submit'))
            {
                if (!check_form_key('jeb_snp'))
                {
                    trigger_error('FORM_INVALID', E_USER_WARNING);
                }
                $word_to_search = $this->request->variable('word_to_search', '');
                $word_to_remove = $this->request->variable('word_to_remove', '');
                if ($word_to_remove)
                {
                    $this->remove_common_word($word_to_remove);
                }
                if ($word_to_search)
                {
                    $this->add_common_word($word_to_search);
                }
            }
            $data = $this->select_common_words();
            foreach ($data as $row)
            {
                $group = array(
                    'WORD_ID'    => $row['word_id'],
                    'WORD_TEXT'    => $row['word_text'],
                    'WORD_COMMON'    => $row['word_common'],
                    'WORD_COUNT'    => $row['word_count'],
                );
                $this->template->assign_block_vars('postrow', $group);
            }
            $this->template->assign_var('TITLE', $cfg['title']);
            return $this->helper->render($tpl_name, $cfg['title']);
        }
    }/*}}}*/

    private function handle_mysql_search($cfg)/*{{{*/
    {
        $tpl_name = $cfg['tpl_name'];
        if ($tpl_name)
        {
            $time = microtime(true);
            add_form_key('jeb_snp');
            // IF SUBMITTED
            $data = [];
            $total = 0;
            $pg = new \jeb\snahp\core\pagination();
            $base_url = '/app.php/snahp/search_util/handle_mysql_search/';
            $word_to_search = $this->request->variable('word_to_search', '');
            $per_page = $this->request->variable('per_page', 20);
            $per_page = $per_page < 200 ? $per_page : 200;
            $start = $this->request->variable('start', 0);
            $base_url .= '?word_to_search=' . $word_to_search;
            $b_posts = $this->request->variable('posts', '0');
            [$data, $total] = $this->mysql_search($word_to_search, $per_page, $start, $b_posts);
            $pagination = $pg->make($base_url, $total, $per_page, $start);
            $this->template->assign_vars([
                'PAGINATION' => $pagination,
            ]);
            $count = $start+1;
            foreach ($data as $row)
            {
                $group = array(
                    'TOPIC_TITLE' => $row['topic_title'],
                    'TOPIC_ID'    => $row['topic_id'],
                    'POST_ID'     => isset($row['post_id']) ? "#p{$row['post_id']}" : '',
                    'ID'          => $count,
                );
                $count += 1;
                $this->template->assign_block_vars('postrow', $group);
            }
            $duration = (string) (microtime(true) - $time);
            $this->template->assign_vars([
                'PHRASE' => $word_to_search,
                'DURATION' => $duration,
                'TITLE' => $cfg['title'],
                'B_POSTS' => $b_posts,
            ]);
            return $this->helper->render($tpl_name, $cfg['title']);
        }
    }/*}}}*/

    private function mysql_search($strn, $per_page=10, $start=0, $b_posts=true)/*{{{*/
    {
        $strn = $this->db->sql_escape($strn);
        if ($b_posts)
        {
            $sql_ary = [
                'SELECT'   => 'COUNT(*) as count',
                // 'SELECT'   => 'COUNT(*) as count t.topic_title, t.topic_id',
                'FROM'     => [TOPICS_TABLE => 't'],
                'LEFT_JOIN'	=> [
                    [
                        'FROM'	=> [POSTS_TABLE => 'p'],
                        'ON'	=> 't.topic_id=p.topic_id',
                    ],
                ],
                'WHERE'    => "t.topic_title LIKE '%{$strn}%' OR p.post_text LIKE '%{$strn}%'",
                'ORDER_BY' => 't.topic_id DESC',
            ];
            $sql_count = $this->db->sql_build_query('SELECT', $sql_ary);
            $sql_ary['SELECT'] =  't.topic_title, t.topic_id, p.post_id';
            $sql = $this->db->sql_build_query('SELECT', $sql_ary);
        }
        else
        {
            $sql_count = 'SELECT COUNT(*) as count from ' . TOPICS_TABLE . ' WHERE topic_title LIKE ' . "'%{$strn}%'";
            $sql = 'SELECT * from ' . TOPICS_TABLE . ' WHERE topic_title LIKE ' . "'%{$strn}%' ORDER BY topic_id DESC";
        }
        $result = $this->db->sql_query($sql_count);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        $total = $row['count'];
        $result = $this->db->sql_query_limit($sql, $per_page, $start);
        $rowset = $this->db->sql_fetchrowset($result);
        $this->db->sql_freeresult($result);
        return [$rowset, $total];
    }/*}}}*/

    private function select_common_words()/*{{{*/
    {
        $sql = 'SELECT * from ' . SEARCH_WORDLIST_TABLE . ' WHERE word_common=1';
        $result = $this->db->sql_query($sql);
        $rowset = $this->db->sql_fetchrowset($result);
        $this->db->sql_freeresult($result);
        return $rowset;
    }/*}}}*/

    private function normalize_topic($strn)/*{{{*/
    {
        setlocale(LC_ALL, 'C.UTF-8');
        $new = iconv('UTF-8', 'ASCII//TRANSLIT', $strn);
        return $strn . ' ' . $new;
    }/*}}}*/

    public function index_topic($cfg)/*{{{*/
    {
        // From includes/functions_posting.php
        global $phpbb_root_path, $phpEx, $auth, $config, $db, $user, $phpbb_dispatcher;
        // Set indexing parameters
        $tid = $this->request->variable('t', 0);
        if (!$tid)
        {
            trigger_error('You must provide topic id. Error Code: 245903c3ce');
        }
        // Select the search method and do some additional checks to ensure it can actually be utilised
        $search_type = $config['search_type'];
        if (!class_exists($search_type))
        {
            trigger_error('NO_SUCH_SEARCH_MODULE. Error Code: 7ad6fd4492');
        }
        // Check topic exists
        $topic_data = $this->select_topic($tid);
        if (!$topic_data)
        {
            trigger_error('That topic does not exist. Error Code: 8d394ff0fd');
        }
        // Check if search enhancer is allowed
        if (!$this->is_search_enhancer_allowed($topic_data))
        {
            meta_refresh(2, '/viewtopic.php?t=' . (int)$tid);
            trigger_error('Your group does not have the permission to index a topic. Error Code: 8a856e72a8');
        }
        $post_id = $topic_data['topic_first_post_id'];
        $data_ary = $this->select_post($post_id);
        // Indexer Parameters
        $mode = 'edit_first_post';
        $poster_id = $data_ary['poster_id'];
        $message = $data_ary['post_text'];
        // Get contain within [STBB] tags and mark it as title
        // [{$this->STBB}]{TEXT}[/{$this->STBB}]
        $as_title = $this->collect_STBB_content($message);
        $subject = $data_ary['post_subject'] . ' ' . $as_title;
        $subject = $this->normalize_topic($subject);
        $forum_id = $data_ary['forum_id'];
        $error = false;
        $search = new \jeb\snahp\core\fulltext_native_local($error, $phpbb_root_path, $phpEx, $auth, $config, $db, $user, $phpbb_dispatcher);
        // Manually set min length to 2
        $search->word_length = ['min' => 3, 'max' => 30];
        // For subject word_length is hardcode to be 2
        $search->index_remove([$post_id], [$poster_id], [$forum_id]);
        $search->index($mode, $post_id, $message, $subject, $poster_id, $forum_id);
        // Insert a record of the manual indexing
        $user_id = $user->data['user_id'];
        $username_clean = $user->data['username_clean'];
        $this->insert_manual_search_post($post_id, $user_id, $username_clean);
        meta_refresh(2, '/viewtopic.php?t=' . (int)$tid);
        trigger_error('The selected topic was indexed with enhanced options.');
    }/*}}}*/

    private function insert_manual_search_post($post_id, $user_id, $username_clean)/*{{{*/
    {
        $tbl = $this->container->getParameter('jeb.snahp.tables');
        $data = [
            'post_id' => $post_id,
            'user_id' => $user_id,
            'username_clean' => $username_clean,
            'index_time' => time(),
        ];
        $sql = 'REPLACE INTO ' . $tbl['manual_search_posts'] .
            $this->db->sql_build_array('INSERT', $data);
        $this->db->sql_query($sql);
    }/*}}}*/

    private function snp_strip_bbcode($text)/*{{{*/
    {
        $uid = $bitfield = '';
        $flags = 0;
        generate_text_for_storage($text, $uid, $bitfield, $flags, true, true, true);
        strip_bbcode($text);
        return $text;
    }/*}}}*/

    private function collect_STBB_content($text)/*{{{*/
    {
        $uid = ''; $flags = 0;
        $text = generate_text_for_edit($text, $uid, $flags)['text'];
        $ptn = "#\[{$this->STBB}](.{1,120})\[/{$this->STBB}]#ism";
        $b_match = preg_match_all($ptn, $text, $match_all);
        if (!$b_match) return '';
        $match_all = $match_all[1];
        $text = join(' ', $match_all);
        $text = $this->snp_strip_bbcode($text);
        return $text;
    }/*}}}*/

}
