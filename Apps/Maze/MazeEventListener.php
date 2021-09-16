<?php
namespace jeb\snahp\Apps\Maze;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

define("MAZE", "phpbb_snahp_maze");
define("MAZE_USER", "phpbb_snahp_maze_user");

class MazeEventListener implements EventSubscriberInterface
{
    public function __construct($config, $sauth, $helper)
    {
        $this->config = $config;
        $this->sauth = $sauth;
        $this->helper = $helper;
        $this->userId = $this->sauth->userId;
    }

    public static function getSubscribedEvents()
    {
        return [
            "core.viewtopic_post_rowset_data" => [["setMazeParams", 1]],
            "core.viewtopic_modify_post_row" => [["showMaze", 1]],
        ];
    }

    public function setMazeParams($event)
    {
        $row = $event["row"];
        $rowset_data = $event["rowset_data"];
        $rowset_data["snp_maze_enable"] = $row["snp_maze_enable"];
        $event["rowset_data"] = $rowset_data;
    }

    public function showMaze($event)
    {
        $row = $event["row"];
        // snp_maze_enable must be set from setMazeParams
        if ($row["snp_maze_enable"]) {
            $topicPoster = $event["topic_data"]["topic_poster"];
            $postRow = $event["post_row"];
            $topicId = (int) $row["topic_id"];
            $thanked = $this->helper->hasThankedTopic($this->userId, $topicId);
            $isDev = $this->sauth->is_dev();
            $isOP = $topicPoster == $this->userId;
            if ($isOP || $isDev || $thanked) {
                $postId = (int) $postRow["POST_ID"];
                $data = ["postId" => $postId];
                $attributeData = convertArrayToHTMLAttribute($data);
                $text =
                    '<dl class="hidebox unhide"><dd><div class="rx_maze" data-data="' .
                    $attributeData .
                    '"></div></dd></dl>';
            } else {
                $text =
                    '<dl class="hidebox hi"><dd><center>' .
                    'Please click <i aria-hidden="true" class="icon fa-fw fa-thumbs-o-up"></i>' .
                    '<span class="sr-only"> the thanks button </span>to show</center></dd></dl>';
            }
            $postRow["MESSAGE"] = $postRow["MESSAGE"] . $text;
            $event["post_row"] = $postRow;
        }
    }
}
