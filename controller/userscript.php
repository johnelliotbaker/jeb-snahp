<?php
namespace jeb\snahp\controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use jeb\snahp\core\base;

class userscript extends base
{
    protected $prefix;

    public function __construct($prefix, $sauth, $topicBumpHelper)
    {
        $this->prefix = $prefix;
        $this->sauth = $sauth;
        $this->topicBumpHelper = $topicBumpHelper;
        $this->userId = $sauth->userId;
    }

    public function handle($mode)
    {
        switch ($mode) {
            case "bump_topic":
                $cfg["tpl_name"] = "";
                $cfg["base_url"] = "/app.php/snahp/userscript/bump_topic/";
                $cfg["title"] = "Bump Topic";
                return $this->handle_bump_topic($cfg);
                break;
            case "username":
                return $this->handle_username();
            case "userid":
                return $this->handle_userid();
            case "set_usercolor":
                return $this->set_user_colour_as_json();
            default:
                break;
        }
        trigger_error("Nothing to see here. Move along.");
    }

    private function get_user_colour($user_id)
    {
        $sql =
            "SELECT user_colour FROM " .
            USERS_TABLE .
            " WHERE user_id=${user_id}";
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        return isset($row["user_colour"]) ? $row["user_colour"] : false;
    }

    private function is_valid_user($user_id)
    {
        $sql = "SELECT * FROM " . USERS_TABLE . " WHERE user_id=${user_id}";
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        return !!$row;
    }

    private function is_valid_user_colour($user_colour)
    {
        return ctype_xdigit($user_colour) && strlen($user_colour) == 6;
    }

    private function reset_user_colour($user_id)
    {
        $sql = "SELECT * FROM " . USERS_TABLE . " WHERE user_id=${user_id}";
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        if (!$row) {
            return false;
        }
        $group_id = $row["group_id"];
        $sql = "SELECT * FROM " . GROUPS_TABLE . " WHERE group_id=${group_id}";
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        if (!$row) {
            return false;
        }
        $group_colour = $row["group_colour"];
        $this->set_user_colour($user_id, $group_colour);
        return true;
    }

    private function set_user_colour($user_id, $user_colour)
    {
        $data = ["user_colour" => $user_colour];
        $sql =
            "UPDATE " .
            USERS_TABLE .
            '
            SET ' .
            $this->db->sql_build_array("UPDATE", $data) .
            "
            WHERE user_id=${user_id}";
        $this->db->sql_query($sql);
        return $this->db->sql_affectedrows() > 0;
    }

    public function set_user_colour_as_json()
    {
        $this->reject_non_dev();
        $profile_id = $this->request->variable("p", 0);
        $user_colour = $this->request->variable("c", "000000");
        $reset = $this->request->variable("r", 0);
        if (!$profile_id || !$this->is_valid_user($profile_id)) {
            return new JsonResponse(["status" => "Invalid user"]);
        }
        if ($reset) {
            $b_success = $this->reset_user_colour($profile_id) ? 1 : 0;
            return new JsonResponse(["status" => $b_success]);
        }
        if (!$this->is_valid_user_colour($user_colour)) {
            return new JsonResponse(["status" => "Invalid user color"]);
        }
        $b_success = $this->set_user_colour($profile_id, $user_colour) ? 1 : 0;
        return new JsonResponse(["status" => $b_success]);
    }

    public function handle_userid()
    {
        $partial = $this->request->variable("partial", "");
        $partial = utf8_clean_string($partial);
        $data = [];
        if ($partial and strlen($partial) > 2) {
            $sql =
                "SELECT user_id, username_clean FROM " .
                USERS_TABLE .
                " WHERE username_clean LIKE '$partial%'";
            $result = $this->db->sql_query_limit($sql, 10);
            $rowset = $this->db->sql_fetchrowset($result);
            $this->db->sql_freeresult($result);
            foreach ($rowset as $row) {
                $tmp = [];
                $tmp["username"] = $row["username_clean"];
                $tmp["user_id"] = $row["user_id"];
                $data[] = $tmp;
            }
        }
        $js = new \phpbb\json_response();
        $js->send($data);
    }

    public function handle_username()
    {
        $partial = $this->request->variable("partial", "");
        $partial = utf8_clean_string($partial);
        $data = [];
        if ($partial and strlen($partial) > 2) {
            $sql =
                "SELECT username_clean FROM " .
                USERS_TABLE .
                " WHERE username_clean LIKE '$partial%'";
            $result = $this->db->sql_query_limit($sql, 10);
            $rowset = $this->db->sql_fetchrowset($result);
            $this->db->sql_freeresult($result);
            foreach ($rowset as $row) {
                $data[] = $row["username_clean"];
            }
        }
        $js = new \phpbb\json_response();
        $js->send($data);
    }

    public function get_or_reject_topic_data($tid)
    {
        if (!$tid) {
            trigger_error("No topic_id was provided.");
        }
        $topicdata = $this->select_topic($tid);
        if (!$topicdata) {
            trigger_error("That topic does not exist.");
        }
        return $topicdata;
    }

    public function enable_bump_topic($bEnable = true)
    {
        $time = time();
        $tid = $this->request->variable("t", "");
        $scriptStatus = [];
        $ctx = $this->topicBumpHelper->getBumpContext($tid);
        $perm = $ctx["permissions"];
        $bumpData = $ctx["bumpData"];
        $topicData = $ctx["topicData"];
        $def = $ctx["def"];
        if (!$topicData) {
            trigger_error("Topic $tid does not exist.");
        }
        $forumId = $topicData["forum_id"];
        $fidListings = $this->config["snp_fid_listings"];
        $aFid = $this->select_subforum($fidListings);
        if (!in_array($forumId, $aFid)) {
            trigger_error("You can only bump topics in listings.");
        }
        if ($bEnable) {
            if ($perm["enable"]) {
                if (!$bumpData) {
                    // If no bump data, create bump
                    $this->create_bump_topic($topicData);
                    $scriptStatus[] = "You can now start bumping this topic.";
                } else {
                    $data = [
                        "status" => $def["enable"],
                        "topic_time" => $time,
                        "n_bump" => 0,
                    ];
                    $this->update_bump_topic($tid, $data);
                    $scriptStatus[] = "Bumping has been enabled.";
                }
            } else {
                // Error if op is trying to enable a topic disabled by mod.
                trigger_error(
                    "You cannot enable bumping, please ask a moderator."
                );
            }
        } else {
            if ($perm["disable"]) {
                $data = [
                    "status" => $def["disable"],
                    "topic_time" => 0,
                    "n_bump" => 0,
                ];
                $this->update_bump_topic($tid, $data);
                $scriptStatus[] = "Bumping has been disabled.";
            } else {
                trigger_error("You cannot disble bumping.");
            }
        }
        $strn = implode("<br>", $scriptStatus);
        $returnUrl = "/viewtopic.php?t=" . $tid;
        if (array_key_exists("forum_id", $topicData)) {
            $returnUrl .= "&f=" . $topicData["forum_id"];
        }
        meta_refresh(2, $returnUrl);
        trigger_error($strn);
    }

    public function phpbb_bump_topic(
        $forum_id,
        $topic_id,
        $post_data,
        $bump_time = false
    ) {
        // From includes/functions_posting.php
        global $config, $db, $user, $phpEx, $phpbb_root_path, $phpbb_log;
        if ($bump_time === false) {
            $bump_time = time();
        }
        // Begin bumping
        $db->sql_transaction("begin");
        $sql =
            "UPDATE " .
            TOPICS_TABLE .
            "
            SET topic_last_post_time = $bump_time,
            topic_time = $bump_time,
            topic_bumped = 1,
            topic_bumper = " .
            $user->data["user_id"] .
            "
            WHERE topic_id = $topic_id";
        $db->sql_query($sql);
        $sql =
            "UPDATE " .
            FORUMS_TABLE .
            "
            SET forum_last_post_id = " .
            $post_data["topic_last_post_id"] .
            ",
            forum_last_poster_id = " .
            $post_data["topic_last_poster_id"] .
            ",
            forum_last_post_subject = '" .
            $db->sql_escape($post_data["topic_last_post_subject"]) .
            "',
            forum_last_post_time = $bump_time,
            forum_last_poster_name = '" .
            $db->sql_escape($post_data["topic_last_poster_name"]) .
            "',
            forum_last_poster_colour = '" .
            $db->sql_escape($post_data["topic_last_poster_colour"]) .
            "'
            WHERE forum_id = $forum_id";
        $db->sql_query($sql);
        $db->sql_transaction("commit");
        markread("post", $forum_id, $topic_id, $bump_time);
        markread("topic", $forum_id, $topic_id, $bump_time);
        if ($config["load_db_lastread"] && $user->data["is_registered"]) {
            $sql =
                'SELECT mark_time
                FROM ' .
                FORUMS_TRACK_TABLE .
                '
                WHERE user_id = ' .
                $user->data["user_id"] .
                '
                AND forum_id = ' .
                $forum_id;
            $result = $db->sql_query($sql);
            $f_mark_time = (int) $db->sql_fetchfield("mark_time");
            $db->sql_freeresult($result);
        } elseif (
            $config["load_anon_lastread"] ||
            $user->data["is_registered"]
        ) {
            $f_mark_time = false;
        }
        if (
            ($config["load_db_lastread"] && $user->data["is_registered"]) ||
            $config["load_anon_lastread"] ||
            $user->data["is_registered"]
        ) {
            $sql =
                'SELECT forum_last_post_time
                FROM ' .
                FORUMS_TABLE .
                '
                WHERE forum_id = ' .
                $forum_id;
            $result = $db->sql_query($sql);
            $forum_last_post_time = (int) $db->sql_fetchfield(
                "forum_last_post_time"
            );
            $db->sql_freeresult($result);
            update_forum_tracking_info(
                $forum_id,
                $forum_last_post_time,
                $f_mark_time,
                false
            );
        }
    }

    public function bump_topic()
    {
        $tid = $this->request->variable("t", "");
        $ctx = $this->topicBumpHelper->getBumpContext($tid);
        $perm = $ctx["permissions"];
        $bumpData = $ctx["bumpData"];
        $topicData = $ctx["topicData"];
        $def = $ctx["def"];
        $bBump = $perm["bump"];
        if (!$bBump) {
            trigger_error('You don\'t have the permission to bump this topic.');
        }
        $cooldown = $this->topicBumpHelper->getBumpCooldown($this->userId);
        $bMod = $this->is_dev();
        $time = time();
        $scriptStatus = [];
        if (!$bumpData) {
            $this->enable_bump_topic($b_enable = true);
        }
        $nBump = $bumpData["n_bump"];
        if (
            !$bMod &&
            $nBump > 0 &&
            $time < $bumpData["topic_time"] + $cooldown
        ) {
            $returnUrl = "/viewtopic.php?t=" . $tid;
            if (array_key_exists("forum_id", $topicData)) {
                $returnUrl .= "&f=" . $topicData["forum_id"];
            }
            meta_refresh(8, $returnUrl);
            $status = [
                "You cannot bump again until " .
                $this->user->format_date($bumpData["topic_time"] + $cooldown) .
                ".",
                "You may ask a moderator to bump this topic for you.",
            ];
            trigger_error(implode("<br>", $status));
        }
        $time = time();
        $forumId = $topicData["forum_id"];
        $topicId = $topicData["topic_id"];
        $this->phpbb_bump_topic($forumId, $topicId, $topicData);
        $bumpData = $this->select_bump_topic($tid);
        $data = [
            "topic_time" => $time,
            "n_bump" => $bumpData["n_bump"] + 1,
        ];
        $this->update_bump_topic($tid, $data);
        $title = $topicData["topic_title"];
        $strn = "$title has been bumped on " . $this->user->format_date($time);
        $returnUrl = "/viewtopic.php?t=" . $tid;
        if (array_key_exists("forum_id", $topicData)) {
            $returnUrl .= "&f=" . $topicData["forum_id"];
        }
        meta_refresh(2, $returnUrl);
        trigger_error($strn);
    }

    public function handle_bump_topic($cfg)
    {
        $this->reject_anon();
        $userId = (int) $this->user->data["user_id"];
        if ($this->topicBumpHelper->userIsBannedOrRemoveBumpUser($userId)) {
            $bumpUser = $this->topicBumpHelper->getBumpUser($userId);
            $end = $this->user->format_date($bumpUser["ban_end"]);
            $reason = $bumpUser["ban_reason"];
            $message = [];
            $message[] = "Your bump privileges have been suspended until ${end}.";
            if ($reason) {
                $message[] = "Reason: ${reason}";
            }
            $message[] = "Error Code: f5f39c3288";
            $message = implode("<br/>", $message);
            trigger_error($message);
        }
        $snp_bump_b_topic = $this->config["snp_bump_b_topic"];
        if (!$snp_bump_b_topic) {
            trigger_error("The administrator has disabled bumping sitewide.");
        }
        $action = $this->request->variable("action", "");
        if (!$action) {
            trigger_error("Must specify a valid action.");
        }
        switch ($action) {
            case "enable":
                $this->enable_bump_topic(true);
                break;
            case "disable":
                $this->enable_bump_topic(false);
                break;
            case "bump":
                $this->bump_topic($cfg);
                break;
            default:
                trigger_error("Invalid action.");
        }
        $return_url = "/viewtopic.php?t=" . $tid;
        if (array_key_exists("forum_id", $topic_data)) {
            $return_url .= "&f=" . $topic_data["forum_id"];
        }
        meta_refresh(2, $return_url);
        trigger_error($strn);
    }

    public function handle_thanks_given($cfg)
    {
        $this->reject_anon();
        $tpl_name = $cfg["tpl_name"];
        if ($tpl_name) {
            $base_url = $cfg["base_url"];
            $pagination = $this->container->get("pagination");
            $per_page = $this->config["posts_per_page"];
            $start = $this->request->variable("start", 0);
            [$data, $total] = $this->select_thanks_given($per_page, $start);
            $pagination->generate_template_pagination(
                $base_url,
                "pagination",
                "start",
                $total,
                $per_page,
                $start
            );
            foreach ($data as $row) {
                $tid = $row["topic_id"];
                $pid = $row["post_id"];
                $post_time = $this->user->format_date($row["post_time"]);
                $u_details = "/viewtopic.php?t=$tid&p=$pid#p$pid";
                $poster_id = $row["poster_id"];
                $poster_name = $row["username"];
                $poster_colour = $row["user_colour"];
                $thanks_time = $this->user->format_date($row["thanks_time"]);
                $group = [
                    "FORUM_ID" => $row["forum_id"],
                    "TOPIC_ID" => $row["topic_id"],
                    "POST_SUBJECT" => $row["post_subject"],
                    "POST_TIME" => $post_time,
                    "POSTER_ID" => $poster_id,
                    "POSTER_NAME" => $poster_name,
                    "POSTER_COLOUR" => $poster_colour,
                    "THANKS_TIME" => $thanks_time,
                    "U_VIEW_DETAILS" => $u_details,
                ];
                $this->template->assign_block_vars("postrow", $group);
            }
            $this->template->assign_var("TITLE", $cfg["title"]);
            return $this->helper->render($tpl_name, $cfg["title"]);
        }
    }

    public function handle_favorite($cfg)
    {
        $this->reject_anon();
        $tpl_name = $cfg["tpl_name"];
        if ($tpl_name) {
            $base_url = $cfg["base_url"];
            $fid_listings = $this->config["snp_fid_listings"];
            $pagination = $this->container->get("pagination");
            $per_page = $this->config["posts_per_page"];
            $start = $this->request->variable("start", 0);
            $sort_mode = $cfg["sort_mode"];
            [$data, $total] = $this->select_one_day(
                $fid_listings,
                $per_page,
                $start,
                $sort_mode
            );
            $pagination->generate_template_pagination(
                $base_url,
                "pagination",
                "start",
                $total,
                $per_page,
                $start
            );
            foreach ($data as $row) {
                $tid = $row["topic_id"];
                $topic_time = $this->user->format_date($row["topic_time"]);
                $u_details = "/viewtopic.php?t=" . $tid;
                $poster_id = $row["topic_poster"];
                $poster_name = $row["topic_first_poster_name"];
                $poster_colour = $row["topic_first_poster_colour"];
                $lp_id = $row["topic_last_poster_id"];
                $lp_name = $row["topic_last_poster_name"];
                $lp_colour = $row["topic_last_poster_colour"];
                $lp_subject = $row["topic_last_post_subject"];
                $lp_time = $this->user->format_date(
                    $row["topic_last_post_time"]
                );
                $group = [
                    "FORUM_ID" => $row["forum_id"],
                    "TOPIC_ID" => $row["topic_id"],
                    "TOPIC_TITLE" => $row["topic_title"],
                    "TOPIC_TIME" => $topic_time,
                    "POSTER_ID" => $poster_id,
                    "POSTER_NAME" => $poster_name,
                    "POSTER_COLOUR" => $poster_colour,
                    "LP_ID" => $lp_id,
                    "LP_NAME" => $lp_name,
                    "LP_COLOUR" => $lp_colour,
                    "LP_SUBJECT" => $lp_subject,
                    "LP_TIME" => $lp_time,
                    "TOPIC_VIEWS" => $row["topic_views"],
                    "FORUM_NAME" => $row["forum_name"],
                    "REPLIES" => $row["topic_posts_approved"] - 1,
                    "U_VIEW_DETAILS" => $u_details,
                ];
                $this->template->assign_block_vars("postrow", $group);
            }
            $this->template->assign_var("TITLE", $cfg["title"]);
            return $this->helper->render($tpl_name, $cfg["title"]);
        }
    }
}
