<?php

namespace jeb\snahp\controller;

use jeb\snahp\core\base;
use Symfony\Component\HttpFoundation\Response;
use \Symfony\Component\HttpFoundation\JsonResponse;

function prn($var) {
    if (is_array($var))
    { foreach ($var as $k => $v) { echo "... $k => "; prn($v); }
    } else { echo "$var<br>"; }
}

class search_util extends base
{

    protected $table_prefix;

    public function __construct($table_prefix)
    {
        $this->table_prefix = $table_prefix;
    }

	public function handle($mode)
    {
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
                $this->reject_non_moderator();
                $cfg['tpl_name'] = '@jeb_snahp/search_util/component/handle_common_words/base.html';
                $cfg['base_url'] = '/app.php/snahp/search_util/handle_common_words/';
                $cfg['title'] = 'Add or Remove Excluded Search Terms';
                $cfg['b_feedback'] = false;
                return $this->handle_common_words($cfg);
                break;
            default:
                break;
        }
        trigger_error('Error Code: 1fdd2c2b80');
    }

    private function remove_common_word($word)
    {
        $sql = 'INSERT INTO ' . SEARCH_WORDLIST_TABLE . '(word_text, word_common, word_count)
            VALUES ' . "('{$word}', 0, 0) " . '
            ON DUPLICATE KEY UPDATE
                word_common=0';
        $this->db->sql_query($sql);
    }

    private function add_common_word($word)
    {
        $sql = 'INSERT INTO ' . SEARCH_WORDLIST_TABLE . '(word_text, word_common, word_count)
            VALUES ' . "('{$word}', 1, 0) " . '
            ON DUPLICATE KEY UPDATE
                word_common=1';
        $this->db->sql_query($sql);
    }

    private function handle_common_words($cfg)
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
                $word_to_add = $this->request->variable('word_to_add', '');
                $word_to_remove = $this->request->variable('word_to_remove', '');
                if ($word_to_remove)
                {
                    $this->remove_common_word($word_to_remove);
                }
                if ($word_to_add)
                {
                    $this->add_common_word($word_to_add);
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

    }

    private function select_common_words()
    {
        $sql = 'SELECT * from ' . SEARCH_WORDLIST_TABLE . ' WHERE word_common=1';
        $result = $this->db->sql_query($sql);
        $rowset = $this->db->sql_fetchrowset($result);
        $this->db->sql_freeresult($result);
        return $rowset;
    }

    private function normalize_topic($strn)
    {
        setlocale(LC_ALL, 'en_US.utf8');
        $new = iconv('UTF-8', 'ASCII//TRANSLIT', $strn);
        return $strn . ' ' . $new;
    }

    public function index_topic($cfg)
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
        $subject = $data_ary['post_subject'];
        $subject = $this->normalize_topic($subject);
        $forum_id = $data_ary['forum_id'];
        $error = false;
        $search = new \jeb\snahp\core\fulltext_native_local($error, $phpbb_root_path, $phpEx, $auth, $config, $db, $user, $phpbb_dispatcher);
        // Manually set min length to 2
        $search->word_length = ['min' => 2, 'max' => 30];
        $search->index_remove([$post_id], [$poster_id], [$forum_id]);
        $search->index($mode, $post_id, $message, $subject, $poster_id, $forum_id);
        // Insert a record of the manual indexing
        $user_id = $user->data['user_id'];
        $username_clean = $user->data['username_clean'];
        $this->insert_manual_search_post($post_id, $user_id, $username_clean);
        meta_refresh(2, '/viewtopic.php?t=' . (int)$tid);
        trigger_error('The selected topic was indexed with enhanced options.');
    }

    private function insert_manual_search_post($post_id, $user_id, $username_clean)
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
    }

}
