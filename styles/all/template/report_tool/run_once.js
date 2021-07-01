const theme = {
  dark: {
    post: {
      background: "rgba(45, 0, 0, 0.9)",
    },
  },
  light: {
    post: {
      background: "rgba(255, 0, 0, 0.05)",
    },
  },
};

class ReportTool {
  constructor() {
    const styleType = $('input[name="snp_style_type"]').val();
    this.style = theme[styleType];
  }
  run = () => {
    const reportedPosts = $("div.reported_post_info");
    reportedPosts.each((index) => {
      const post = $(reportedPosts[index]);
      const reportedTime = post.data("reportedTime");
      const reportButton = post.parent().find('a[title="Report this post"]');
      const container = reportButton.parents().eq(5);
      container.css("backgroundColor", this.style.post.background);
      reportButton.css("opacity", 0.3);
      reportButton.removeAttr("href");
      reportButton.attr("title", "Reported on " + reportedTime);
    });
  };
}

$(document).ready(() => {
  const mod = $('input[name="snp_mod"]').val();
  if (!mod) {
    new ReportTool().run();
  }
});
