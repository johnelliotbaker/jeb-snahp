const markTopic = async (topicId) => {
  if (!topicId) {
    alert("Could not find topic.");
    return;
  }
  const url = `/snahp/posting-violation/mark-topic`;
  const reason = $("#violation-reason").val();
  let mark = 0;
  const markRadios = $("input[name='mark-violation'");
  for (let radio of markRadios) {
    if (radio.checked) {
      mark = radio.value;
    }
  }
  const body = new FormData();
  body.append("submit", "submit");
  body.append("topicId", topicId);
  body.append("reason", reason);
  body.append("mark", mark);
  const resp = await fetch(url, {
    method: "POST",
    cache: "no-cache",
    body,
  });
  if (resp.ok) {
    location.reload();
  } else {
    alert(
      "Could not mark topic for violation. Probably can't find the topic id."
    );
  }
};
