<?php
namespace jeb\snahp\Apps\Jukebox;

class JukeboxHelper
{
    public function __construct($db, $configText, $tbl, $sauth)
    {
        $this->db = $db;
        $this->configText = $configText;
        $this->tbl = $tbl;
        $this->sauth = $sauth;
        $this->userId = $sauth->userId;
    }

    public function selectCandidates($numPosts)
    {
        $topicId = $this->sauth->is_dev_server() ? 21121 : 218385;
        $sqlArray = [
            "SELECT" => "a.post_text, b.username, b.user_colour, b.user_avatar",
            "FROM" => [POSTS_TABLE => "a"],
            "LEFT_JOIN" => [
                [
                    "FROM" => [USERS_TABLE => "b"],
                    "ON" => "a.poster_id=b.user_id",
                ],
            ],
            "WHERE" => "a.topic_id=${topicId}",
            "ORDER_BY" => "a.post_id DESC",
        ];
        $sql = $this->db->sql_build_query("SELECT", $sqlArray);
        $result = $this->db->sql_query_limit($sql, $numPosts);
        $rowset = $this->db->sql_fetchrowset($result);
        $this->db->sql_freeresult($result);
        return $rowset;
    }

    public function save($data)
    {
        $this->configText->set("snp_jukebox_data", json_encode($data));
    }

    public function load()
    {
        $data = htmlspecialchars_decode(
            $this->configText->get("snp_jukebox_data")
        );
        $data = json_decode($data, true);
        return $data;
    }

    public function getJukeboxData()
    {
        return $this->load();
    }

    public function updateList($numPosts = 2000)
    {
        $rowset = $this->selectCandidates($numPosts);
        $rowset = $this->process($rowset);
        $this->save($rowset);
    }

    public function process($rowset)
    {
        $limit = 100;
        $res = [];
        $count = 0;
        foreach ($rowset as $row) {
            $postText = $row["post_text"];
            $data = $this->extractImdb($postText);
            if (isset($data["items"])) {
                $items = $data["items"];
                foreach ($items as $item) {
                    $item["dj"] = [
                        "username" => $row["username"],
                        "color" => "#" . $row["user_colour"],
                        "avatar" => $row["user_avatar"],
                    ];
                    $res[] = $item;
                    $count += 1;
                    if ($count >= $limit) {
                        return $res;
                    }
                }
            }
        }
        return $res;
    }

    private function extractImdb($message)
    {
        preg_match("#\<s>\[tub]</s>(.*?)<e>\[/tub]</e>#s", $message, $match);
        return json_decode($match[1], true);
    }
}
