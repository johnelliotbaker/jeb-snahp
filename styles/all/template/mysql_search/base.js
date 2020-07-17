const MysqlSearch = {};

MysqlSearch.makeTopicBBCode = function (tid, title) {
  return "[url=/viewtopic.php?t=" + tid + "]" + title + "[/url]";
};

MysqlSearch.exportBBCode = function (e) {
  const trs = $("#mysql_search_table_body tr");
  const arr = [];
  for (let tr of trs) {
    const td = $(tr).find("td:eq(1)");
    const tid = td.data("topicid");
    const entry = this.makeTopicBBCode(tid, td.text().trim());
    arr.push(entry);
  }
  const result = arr.join("\r\n");
  Clipboard.copy(result);
};
