var Rh_tags_tree = {};

Rh_tags_tree.append_forum_id = function(e)
{
    $target = $(e.target);
    var data = $target.data('forum_id');
    $in_forumids = $('input[name=forum_ids]');
    $forum_ids = $in_forumids.val();
    if ($forum_ids)
    {
        $forum_ids += ',' + data;
    }
    else
    {
        $forum_ids += data;
    }
    $in_forumids.val($forum_ids);
}
