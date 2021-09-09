<?php

function getTopicData($topicId, $cacheCooldown = 0)
{
    global $db;
    $topicId = (int) $topicId;
    $sql = "SELECT * FROM " . TOPICS_TABLE . " WHERE topic_id={$topicId}";
    $result = $db->sql_query($sql, $cacheCooldown);
    $row = $db->sql_fetchrow($result);
    $db->sql_freeresult($result);
    return $row;
}
