<?php
namespace jeb\snahp\Apps\Coop;

require_once "/var/www/forum/ext/jeb/snahp/core/Rest/Utils.php";
require_once "/var/www/forum/ext/jeb/snahp/core/Rest/Serializers.php";

class CoopHelper
{
    public $db;
    public $sauth;
    public function __construct(
        $db,
        $tbl,
        $sauth,
        $Forum,
        $Topic,
        $Post,
        $PhpbbPost,
        $ThanksUser
    ) {
        $this->db = $db;
        $this->tbl = $tbl;
        $this->sauth = $sauth;
        $this->Forum = $Forum;
        $this->Topic = $Topic;
        $this->Post = $Post;
        $this->PhpbbPost = $PhpbbPost;
        $this->ThanksUser = $ThanksUser;
        $this->userId = $sauth->userId;
        $this->maxTopicCount = 20;
    }

    public function canViewPostsInTopic($userId, $topicId)
    {
        $topic = $this->Topic->getObject("id=?", [$topicId]);
        $forum = $this->Forum->getObject("id=?", [
            $topic["phpbb_snahp_coop_forum_id"],
        ]);
        $phpbbPostId = $forum["phpbb_post"];
        if ($this->sauth->is_dev()) {
            return true;
        }
        $gaveThanks = $this->ThanksUser->hasGivenThanksToPost(
            (int) $userId,
            (int) $phpbbPostId
        );
        return $gaveThanks;
    }

    public function createForum($postId)
    {
        if ($this->PhpbbPost->isOP($postId, $this->userId)) {
            return $this->Forum->createWithPostId($postId);
        }
    }

    public function hideCoopInPost($postId)
    {
        $post = $this->PhpbbPost->get($postId);
        $postText = $post["post_text"];
        if (substr($postText, 0, 3) === "<r>") {
            $ptn =
                "#(<HIDE><s>\[hide]</s>)*<COOP><s>\[coop]</s>(.*?)<e>\[/coop]</e></COOP>(<e>\[/hide]</e></HIDE>)*#is";
            $repl = function ($match) {
                return "<HIDE><s>[hide]</s><COOP><s>[coop]</s>" .
                    $match[2] .
                    "<e>[/coop]</e></COOP><e>[/hide]</e></HIDE>";
            };
        } else {
            $ptn = "#\[coop](.*?)\[/coop]#is";
            $repl = function ($match) {
                return "[hide][coop]" . $match[1] . "[/coop][/hide]";
            };
        }
        $postText = preg_replace_callback(
            $ptn,
            function ($match) use ($repl) {
                return $repl($match);
            },
            $postText,
            1
        );
        $this->PhpbbPost->update($postId, [
            "post_text" => $postText,
        ]);
    }

    public function getTopics($forum, $count)
    {
        $count = (int) $count;
        if (!$forum) {
            return [];
        }
        $queryset = $this->Forum->getOwn($forum, TABLE_COOP_TOPIC, [
            "withCondition" => "1=1 LIMIT $count",
        ]);
        return array_values($this->Forum->appendUserInfo($queryset, "user"));
    }

    public function makeBbCodeElement($text, $classname, $postId)
    {
        $pattern = "#<div class=\"$classname\"></div>#";
        $forum = $this->Forum->fromPhpbbPost($postId);
        $topics = $this->getTopics($forum, $this->maxTopicCount);
        $attributeData = convertArrayToHTMLAttribute([
            "forum" => (int) $forum->id,
            "topics" => $topics,
        ]);
        $repl = "<div class=\"$classname\" data-data=\"$attributeData\"></div>";
        return preg_replace($pattern, $repl, $text, 1);
    }

    public function makestuff()
    {
        for ($i = 48; $i < 80; $i++) {
            $data = [
                "user" => $i,
                "phpbb_snahp_coop_forum_id" => 1,
                "visible" => "public",
                "created" => 0,
                "modified" => 0,
            ];
            $this->Topic->create($data);
        }
    }

    public function getPostsInTopic($topicId, $count)
    {
        $topicId = (int) $topicId;
        $count = (int) $count;
        $topic = $this->Topic->getObject("id=?", [$topicId]);
        if (!$topic) {
            return [];
        }
        $queryset = $this->Topic->getOwn($topic, TABLE_COOP_POST, [
            "withCondition" => "visible='public' LIMIT $count",
        ]);
        $data = $this->Topic->appendUserInfo($queryset, "user");
        if (is_array($data)) {
            foreach ($data as $post) {
                $post->user_colour = "#" . $post->user_colour;
                $post->author = (int) $post->user_id === $this->userId;
                $post->mod = $this->sauth->is_dev();
            }
        }
        return array_values($data);
    }
}
