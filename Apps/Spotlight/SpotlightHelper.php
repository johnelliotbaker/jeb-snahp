<?php
namespace jeb\snahp\Apps\Spotlight;

class SpotlightHelper
{
    const MAX_PER_USER = 8;
    const MAX_LIST = 80;
    const CACHE_TIMEOUT = 600;

    public function __construct($db, $template, $cache, $config, $tbl, $sauth)
    {
        $this->db = $db;
        $this->template = $template;
        $this->cache = $cache;
        $this->config = $config;
        $this->tbl = $tbl;
        $this->sauth = $sauth;
        $this->userId = $sauth->userId;
    }

    public function getSpotlightData()
    {
        // $this->cache->purge();
        $name = "spotlightData";
        $cacheTimeout = $this::CACHE_TIMEOUT;
        if (!($data = $this->cache->get($name))) {
            $data = $this->selectCandidates();
            $data = $this->filter($data);
            $this->cache->put($name, $data, $cacheTimeout);
        }
        return $this->cache->get($name);
    }

    public function embedSpotlight()
    {
        // /var/www/forum/ext/jeb/snahp/styles/all/template/spotlight/base.html
        $data = $this->getSpotlightData();
        $attributeData = convertArrayToHTMLAttribute($data);
        $tplData = ["SPOTLIGHT_PROPS" => "data-data=\"$attributeData\""];
        $this->template->assign_vars($tplData);
    }

    private function isImdb($row)
    {
        preg_match(
            "#\<s>\[imdb]</s>(.*?)<e>\[/imdb]</e>#s",
            $row["post_text"],
            $match
        );
        return !!$match;
    }

    private function extractImdb($message)
    {
        preg_match("#\<s>\[imdb]</s>(.*?)<e>\[/imdb]</e>#s", $message, $match);
        return json_decode($match[1], true);
    }

    public function selectCandidates()
    {
        $forumIds = explode(",", $this->config["snp_pg_fid_listing"]);
        $where = $this->db->sql_in_set("t.forum_id", $forumIds);
        $where .= " AND t.topic_first_post_id=p.post_id";
        $orderBy = "t.topic_time DESC";
        $sqlArray = [
            "SELECT" =>
                "t.topic_id, t.topic_title, p.post_text, t.topic_poster, t.topic_first_poster_name, t.topic_first_poster_colour",
            "FROM" => [TOPICS_TABLE => "t"],
            "LEFT_JOIN" => [
                [
                    "FROM" => [POSTS_TABLE => "p"],
                    "ON" => "t.topic_id=p.topic_id",
                ],
            ],
            "WHERE" => $where,
            "ORDER_BY" => $orderBy,
        ];
        $sql = $this->db->sql_build_query("SELECT", $sqlArray);
        $result = $this->db->sql_query_limit($sql, 2000, 0, 0);
        $rowset = $this->db->sql_fetchrowset($result);
        $this->db->sql_freeresult($result);
        return $rowset;
    }

    public function isAcceptedHost($row)
    {
        $title = $row["topic_title"];
        preg_match("#\[mega]|\[zippy]#is", $title, $match);
        return !!$match;
    }

    public function filter($rowset)
    {
        $res = [];
        $jobQueue = $this->getJobQueue();
        foreach ($rowset as $row) {
            if (!$this->isAcceptedHost($row)) {
                continue;
            }
            $topicPoster = $row["topic_poster"];
            if (!isset($this->users[$topicPoster])) {
                $this->users[$topicPoster] = 1;
            }
            if ($this->users[$topicPoster] > $this::MAX_PER_USER) {
                continue;
            }
            foreach ($jobQueue as $job) {
                if ($tmp = $job["processor"]($row)) {
                    $tmp["title"] = htmlspecialchars_decode($tmp["title"]);
                    $res[] = $tmp;
                    if (count($res) >= $this::MAX_LIST) {
                        return $res;
                    }
                    $this->users[$topicPoster] += 1;
                }
            }
        }
        return $res;
    }

    public function getJobQueue()
    {
        return [
            [
                "processor" => function ($row) {
                    if (!$this->isImdb($row)) {
                        return;
                    }
                    $json = $this->extractImdb($row["post_text"], true);
                    $title = $json["title"];
                    $year = (int) $json["year"];
                    $poster_url = $json["poster"];
                    if (!$poster_url || !$title) {
                        return;
                    }
                    $res = [
                        "title" => $title,
                        "poster_url" => $poster_url,
                        "topic_id" => $row["topic_id"],
                        "poster" => $row["topic_first_poster_name"],
                        "colour" => $row["topic_first_poster_colour"],
                        "year" => $year,
                    ];
                    if ($exclusive = $json["exclusive"]) {
                        $res["exclusive"] = true;
                    }
                    return $res;
                },
            ],
        ];
    }
}
