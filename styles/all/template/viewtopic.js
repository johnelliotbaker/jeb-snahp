const TopicTitleMenu = (function () {
  return class {
    constructor() {
      this.setupTopicTitleMenu();
      this.text = "";
    }

    setupTopicTitleMenu() {
      this.topicTitleHeader = $("h2.topic-title");
      this.prependDeboner();
    }

    prependClipboard() {
      const clipboard = $(
        '<i title="Copy Title" class="fa icon fa-clipboard noselect pointer" style="margin-right:0.2em; font-size:14px;"></i>'
      );
      clipboard.click((e) => {
        Clipboard.copy(this.text);
      });
      this.topicTitleHeader.prepend(clipboard);
    }

    prependDeboner() {
      const topicTitleLink = this.topicTitleHeader.find("a");
      const unlink = $(
        '<i title="Debone" class="fa icon fa-unlink noselect pointer" style="margin-right:0.2em; font-size:14px;"></i>'
      );
      const text = topicTitleLink.text().slice();
      this.topicTitleHeader.prepend(unlink);
      unlink.click((e) => {
        topicTitleLink.replaceWith(() => {
          return $(`<span>${text}</span>`);
        });
        this.text = text;
        this.prependClipboard();
        unlink.remove();
      });
    }
  };
})();

$(document).ready(function () {
  const topicTitleMenu = new TopicTitleMenu();
});
