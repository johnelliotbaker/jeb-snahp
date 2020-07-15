<?php

namespace jeb\snahp\Apps\MiniBoard;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

define('DB_PREFIX', 'phpbb_miniboard_');
define('MINIFORUMS_TABLE', DB_PREFIX . "miniforums");
define('MINITOPICS_TABLE', DB_PREFIX . 'minitopics');


class MiniBoardEventListener implements EventSubscriberInterface
{
    protected $user;
    protected $config;
    protected $sauth;
    protected $myHelper;
    public function __construct(/*{{{*/
    ) {
        global $db, $user;
        $this->db = $db;
        $this->user = $user;
        $this->userId = $user->data['user_id'];
    }/*}}}*/

    public static function getSubscribedEvents()/*{{{*//*{{{*/
    {
        return [
            'core.viewtopic_modify_post_row' => [
                ['setForumDataset', 1],
            ],
        ];
    }/*}}}*//*}}}*/

    public function getMiniforum($postId)/*{{{*/
    {
        $cache = 0;
        $postId = (int) $postId;
        $sql = 'SELECT * FROM ' . MINIFORUMS_TABLE . " WHERE mainpost='${postId}'";
        $result = $this->db->sql_query($sql, $cache);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        return $row;
    }/*}}}*/

    public function setForumDataset($event)/*{{{*/
    {
        $post_row = $event["post_row"];
        $msg = &$post_row['MESSAGE'];
        $postId = $post_row['POST_ID'];
        $data = $this->getMiniforum($postId);
        if (!$data) {
            return;
        }

        $pattern = '#<div class="rx_mini_board"></div>#';
        $data['miniforum'] = $data['id'];
        $data['user'] = $this->userId;
        $repl = '<div class="rx_mini_board" data-data="'
            . htmlspecialchars(json_encode($data), ENT_COMPAT)
            . '"></div>';
        $msg = preg_replace($pattern, $repl, $msg, 1);
        $event["post_row"] = $post_row;
    }/*}}}*/
}
