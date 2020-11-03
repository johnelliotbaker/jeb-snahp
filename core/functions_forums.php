<?php

function getRequestForumName($targetForumId)/*{{{*/
{
    global $config;
    $definition = unserialize($config['snp_req_postform_fid']);
    foreach ($definition as $name => $fid) {
        if ($targetForumId === $fid) {
            return $name;
        }
    }
}/*}}}*/

function forumIsRequest($targetForumId)/*{{{*/
{
    global $config;
    $requestsRootForumId = (int) $config['snp_fid_requests'];
    return isInForumBranch($targetForumId, $requestsRootForumId);
}/*}}}*/

function forumIsListing($targetForumId)/*{{{*/
{
    global $config;
    $listingsForumId = (int) $config['snp_fid_listings'];
    return isInForumBranch($targetForumId, $listingsForumId);
}/*}}}*/

function topicIsRequest($targetTopicId)/*{{{*/
{
    $targetForumId = getForumIdFromTopicId($targetTopicId);
    return forumIsRequest($targetForumId);
}/*}}}*/

function topicIsListing($targetTopicId)/*{{{*/
{
    $targetForumId = getForumIdFromTopicId($targetTopicId);
    return forumIsListing($targetForumId);
}/*}}}*/

function getForumIdFromTopicId($topicId)/*{{{*/
{
    include_once __DIR__.'/functions_phpbb.php';
    $topicId = (int) $topicId;
    $topicData = getTopicData($topicId);
    return (int) $topicData['forum_id'];
}/*}}}*/

function getForumBranch($forumId, $type='all', $order='descending', $includeForum=true)/*{{{*/
{
    include_once '/var/www/forum/includes/functions_admin.php';
    return get_forum_branch($forumId, $type, $order, $includeForum);
}/*}}}*/

function isInForumBranch($forumId, $rootForumId)/*{{{*/
{
    $subForums = getForumBranch($rootForumId, 'children');
    $forumIds = array_map(
        function ($subForum) {
            return $subForum['forum_id'];
        },
        $subForums
    );
    return in_array($forumId, $forumIds);
}/*}}}*/
