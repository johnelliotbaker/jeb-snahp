$(document).ready(() => {
  const reportedPosts = $("div.reported_post_info");
  reportedPosts.each((index) => {
    const post = $(reportedPosts[index]);
    const reportedTime = post.data("reportedTime");
    const reportButton = post.parent().find('a[title="Report this post"]');
    reportButton.css("opacity", 0.3);
    reportButton.removeAttr("href");
    reportButton.attr("title", "Reported on " + reportedTime);
  });
});
