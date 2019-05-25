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
            default:
                break;
        }
        trigger_error('Error Code: 1fdd2c2b80');
    }

    private function normalize_topic($strn)
    {
        setlocale(LC_CTYPE, 'en_US.utf8');
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
