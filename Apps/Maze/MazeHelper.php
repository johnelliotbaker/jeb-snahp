<?php
namespace jeb\snahp\Apps\Maze;

require_once "/var/www/forum/ext/jeb/snahp/core/Rest/Utils.php";

class MazeHelper
{
    public function __construct(
        $db,
        $tbl,
        \jeb\snahp\core\auth\user_auth $sauth,
        \jeb\snahp\Apps\Maze\Models\Maze $Maze,
        $MazeUser,
        \jeb\snahp\Apps\Core\Db\query\Post $Post,
        $pageNumberPagination,
        $QuerySetFactory
    ) {
        $this->db = $db;
        $this->tbl = $tbl;
        $this->sauth = $sauth;
        $this->Maze = $Maze;
        $this->MazeUser = $MazeUser;
        $this->Post = $Post;
        $this->paginator = $pageNumberPagination;
        $this->QuerySetFactory = $QuerySetFactory;
        $this->userId = $sauth->userId;
    }

    public function viewUsersInMazes($mazeIds)
    {
        $mazeIds = array_unique(explode(",", $mazeIds));
        [$M, $MU] = [MAZE, MAZE_USER];
        $sqlArray = [
            "SELECT" => "a.*, b.username, b.user_colour",
            "FROM" => [$MU => "a"],
            "LEFT_JOIN" => [
                [
                    "FROM" => [USERS_TABLE => "b"],
                    "ON" => "b.user_id=a.user",
                ],
            ],
            "WHERE" => $this->db->sql_in_set("{$M}_id", $mazeIds),
            "ORDER_BY" => "{$M}_id ASC",
        ];
        $sql = $this->db->sql_build_query("SELECT", $sqlArray);
        $result = $this->db->sql_query($sql);
        $rowset = $this->db->sql_fetchrowset($result);
        $this->db->sql_freeresult($result);
        foreach ($rowset as &$row) {
            $row["log"] = unserialize($row["log"]);
        }
        return $rowset;
    }

    public function logPrivateAccess($userId, $mazeId)
    {
        $MAX_ENTRIES = 100;
        $mazeUser = $this->getMazeUser($userId, $mazeId);
        $log = unserialize($mazeUser->log);
        if (is_array($log)) {
            $log[] = time();
            $count = count($log);
            if ($count > $MAX_ENTRIES) {
                $start = $count - $MAX_ENTRIES;
                $log = array_slice($log, $start);
            }
        } else {
            $log = [time()];
        }
        $mazeUser->log = serialize($log);
        \R::store($mazeUser);
    }

    public function getMazeUser($userId, $mazeId)
    {
        $MAZE = MAZE;
        $mazeUser = $this->MazeUser->getObject("user=? AND ${MAZE}_id=?", [
            $userId,
            $mazeId,
        ]);
        if (!$mazeUser) {
            throwHttpException(
                404,
                "id=${mazeId} not found. Error Code: 0d65041023"
            );
        }
        return $mazeUser;
    }

    public function getPrivateMaze($mazeId, $fields = null)
    {
        $maze = $this->Maze->getObject("id=?", [$mazeId]);
        if (!$maze || $maze->visible !== "private") {
            throwHttpException(404, "Not found. Error Code: 5378a7b083");
        }
        return $this->Maze->exportFields($maze, $fields);
    }

    public function deleteMaze($mazeId)
    {
        $this->Maze->deleteWithId($mazeId);
    }

    public function createMazeInPost($postId)
    {
        $postId = (int) $postId;
        $data = [
            "post" => $postId,
            "visible" => "hidden",
            "text" => "",
            "createdTime" => time(),
            "deadTime" => 0,
        ];
        $this->Maze->create($data);
    }

    public function getPost($postId)
    {
        return $this->Post->get((int) $postId);
    }

    public function getUserTypes($userId, $postId)
    {
        $types = [];
        if ($this->Post->isPoster($postId, $userId)) {
            $types[] = "poster";
        }
        if ($this->sauth->is_dev($userId)) {
            $types[] = "mod";
        }
        return $types;
    }

    public function getUserMazesInPost($userId, $postId)
    {
        $userId = (int) $userId;
        $postId = (int) $postId;
        $fields = ["a.*"];
        $sqlArray = [
            "SELECT" => implode(",", $fields),
            "FROM" => [MAZE => "a"],
            "LEFT_JOIN" => [
                [
                    "FROM" => [MAZE_USER => "b"],
                    "ON" => "a.id = b.phpbb_snahp_maze_id",
                ],
            ],
            "WHERE" => "b.user=$userId AND a.post=$postId",
        ];
        $sql = $this->db->sql_build_query("SELECT", $sqlArray);
        $result = $this->db->sql_query($sql);
        $rowset = $this->db->sql_fetchrowset($result);
        $this->db->sql_freeresult($result);
        return $rowset;
    }

    public function getPublicData($userId, $postId, $userTypes)
    {
        $userId = (int) $userId;
        $postId = (int) $postId;
        // If user is a member of any maze in the post
        $userMazes = $this->getUserMazesInPost($userId, $postId);
        $this->getUserMazesInPost($userId, $postId);
        $rowset = array_values(
            array_map(
                function ($row) {
                    return ["id" => $row["id"]];
                },
                array_filter($userMazes, function ($userMaze) {
                    $private = $userMaze["visible"] == "private";
                    $alive = $userMaze["dead_time"] < $userMaze["created_time"];
                    return $private && $alive;
                })
            )
        );
        if ($userMazes) {
            $res = [
                "user_types" => $userTypes,
                "post_id" => $postId,
                "user_id" => $userId,
                "results" => $rowset,
            ];
            return $this->serialize($res);
        }
        // If not a member
        $fields = ["text"];
        $sqlArray = [
            "SELECT" => implode(",", $fields),
            "FROM" => [MAZE => "a"],
            "WHERE" => "a.post=$postId AND a.dead_time=0 AND a.visible IN ('public')",
        ];
        $sql = $this->db->sql_build_query("SELECT", $sqlArray);
        $result = $this->db->sql_query($sql);
        $rowset = $this->db->sql_fetchrowset($result);
        $this->db->sql_freeresult($result);
        if ($rowset) {
            $res = [
                "user_types" => $userTypes,
                "post_id" => $postId,
                "user_id" => $userId,
                "results" => $rowset,
            ];
            return $this->serialize($res);
        }
    }

    public function getOpData($userId, $postId, $userTypes)
    {
        $userId = (int) $userId;
        $postId = (int) $postId;
        $fields = ["id", "text", "visible", "created_time", "dead_time"];
        $rowset = $this->getMazesWithPost($postId, $fields);
        $res = [
            "user_types" => $userTypes,
            "post_id" => $postId,
            "user_id" => $userId,
            "results" => $rowset,
        ];
        return $this->serialize($res);
    }

    public function getMazesWithPost($postId, $fields = null)
    {
        $postId = (int) $postId;
        if ($fields === null) {
            $fields = ["*"];
        }
        $sqlArray = [
            "SELECT" => implode(",", $fields),
            "FROM" => [MAZE => "a"],
            "WHERE" => "a.post=$postId",
        ];
        $sql = $this->db->sql_build_query("SELECT", $sqlArray);
        $result = $this->db->sql_query($sql);
        $rowset = $this->db->sql_fetchrowset($result);
        $this->db->sql_freeresult($result);
        return $rowset;
    }

    public function attachMazeUserToMazes($mazes)
    {
        $res = [];
        foreach ($mazes as $maze) {
            $mazeId = $maze["id"];
            $res[$mazeId] = $maze;
        }
        if (!$res || count($res) < 1) {
            throwHttpException(404, "No mazes. Error Code: 52833f804c");
        }
        $mazeIds = array_keys($res);
        $sqlArray = [
            "SELECT" => "a.*, b.user_id, b.username, b.user_colour",
            "FROM" => [MAZE_USER => "a"],
            "LEFT_JOIN" => [
                [
                    "FROM" => [USERS_TABLE => "b"],
                    "ON" => "a.user = b.user_id",
                ],
            ],
            "WHERE" => $this->db->sql_in_set("phpbb_snahp_maze_id", $mazeIds),
        ];
        $sql = $this->db->sql_build_query("SELECT", $sqlArray);
        $result = $this->db->sql_query($sql);
        $mazeUsers = $this->db->sql_fetchrowset($result);
        $this->db->sql_freeresult($result);
        foreach ($mazeUsers as $mazeUser) {
            $mazeId = $mazeUser["phpbb_snahp_maze_id"];
            $mazeUser["user_colour"] = "#" . $mazeUser["user_colour"];
            $res[$mazeId]["users"][] = $mazeUser;
        }
        return array_values($res);
    }

    public function getModData($postId, $userTypes)
    {
        $rowset = $this->getMazesWithPost($postId);
        $newMazes = $this->attachMazeUserToMazes($rowset);
        $res = [
            "user_types" => $userTypes,
            "post_id" => $postId,
            "results" => $newMazes,
        ];
        return $this->serialize($res);
    }

    public function serialize($rowset)
    {
        array_walk_recursive($rowset, function (&$value) {
            if (is_numeric($value)) {
                $value = (int) $value;
            }
        });
        return $rowset;
    }

    public function hasThankedTopic($userId, $topicId)
    {
        $sqlArray = [
            "SELECT" => "1",
            "FROM" => [$this->tbl["thanks"] => "a"],
            "WHERE" => "a.user_id=${userId} AND a.topic_id=${topicId}",
        ];
        $sql = $this->db->sql_build_query("SELECT", $sqlArray);
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        return !!$row;
    }

    public function markPost($postId)
    {
        $data = ["snp_maze_enable" => 1];
        $this->Post->update($postId, $data);
    }

    public function unMarkPost($postId)
    {
        $data = ["snp_maze_enable" => 0];
        $this->Post->update($postId, $data);
    }
}
