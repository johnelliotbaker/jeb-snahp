const MiniBoardMCP = {};

MiniBoardMCP.requestForumData = async function (mainpost) {
  const url =
    "http://192.168.2.12:888/app.php/snahp/mini-board/forum?mainpost=" +
    mainpost;
  const resp = await fetch(url, { method: "GET" });
  const json = await resp.json();
  if (!json || json.length !== 1) {
    return false;
  }
  return json[0];
};

MiniBoardMCP.getValueByName = (name, def) => {
  const elems = document.getElementsByName(name);
  if (elems.length !== 1) {
    return def;
  }
  return elems[0].value;
};

MiniBoardMCP.getData = function () {
  return {
    mainpost: this.getValueByName("mainpost", 0),
    moderators: this.getValueByName("moderators", ""),
    owner: this.getValueByName("owner", 0),
  };
};

MiniBoardMCP.getFormData = function (data) {
  const formData = new FormData();
  for (var key in data) {
    formData.append(key, data[key]);
  }
  return formData;
};

MiniBoardMCP.save = async function () {
  const data = this.getData();
  const formData = this.getFormData(data);
  const forum = await this.requestForumData(formData.get("mainpost", 0));
  if (forum) {
    const resp = await this.update(forum.id, data);
  } else {
    const resp = await this.create(formData);
  }
};

MiniBoardMCP.update = async function (id, data) {
  const url = "http://192.168.2.12:888/app.php/snahp/mini-board/forum/" + id;
  console.log(data);
  const resp = await fetch(url, {
    method: "PATCH",
    body: JSON.stringify(data),
  });
  console.log(resp);
  const json = await resp.json();
  return json;
};

MiniBoardMCP.create = async function (formData) {
  const url = "http://192.168.2.12:888/app.php/snahp/mini-board/forum";
  const resp = await fetch(url, { method: "POST", body: formData });
  const json = await resp.json();
  return json;
};

MiniBoardMCP.test = function (e) {
  const target = $(e.currentTarget);
  const tds = target.children();
  const mainpost = parseInt(tds[0].dataset.data);
  const owner = parseInt(tds[1].dataset.data);
  const moderators = tds[2].dataset.data;
  $("#mainpost").val(mainpost);
  $("#owner").val(owner);
  $("#moderators").val(moderators);
};
