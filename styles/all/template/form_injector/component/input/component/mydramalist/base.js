var Mydramalist = {};

Mydramalist.makeMydramalistTemplate = function (data) {
  try {
    var aired_end = data.aired_end;
  } catch (e) {
    var aired_end = "";
  }
  try {
    var aired_start = data.aired_start;
  } catch (e) {
    var aired_start = "";
  }
  try {
    var alt_titles = data.alt_titles.length > 0 ? data.alt_titles : "";
  } catch (e) {
    var alt_titles = "";
  }
  try {
    var certification = data.certification;
  } catch (e) {
    var certification = "";
  }
  try {
    var country = data.country;
  } catch (e) {
    var country = "";
  }
  try {
    var episodes = data.episodes;
  } catch (e) {
    var episodes = "";
  }
  try {
    var genres = data.genres;
  } catch (e) {
    var genres = [];
  }
  try {
    var id = data.id;
  } catch (e) {
    var id = "";
  }
  try {
    var image = data.images.poster;
  } catch (e) {
    var image = "";
  }
  try {
    var language = data.language;
  } catch (e) {
    var language = "";
  }
  try {
    var original_title = data.original_title;
  } catch (e) {
    var original_title = "";
  }
  try {
    var permalink = data.permalink;
  } catch (e) {
    var permalink = "";
  }
  try {
    var rating = data.rating;
  } catch (e) {
    var rating = "";
  }
  try {
    var released = data.released;
  } catch (e) {
    var released = "";
  }
  try {
    var runtime = data.runtime;
  } catch (e) {
    var runtime = "";
  }
  try {
    var status = data.status;
  } catch (e) {
    var status = "";
  }
  try {
    var synopsis = data.synopsis;
  } catch (e) {
    var synopsis = "";
  }
  try {
    var tags = data.tags;
  } catch (e) {
    var tags = [];
  }
  try {
    var title = data.title;
  } catch (e) {
    var title = "";
  }
  try {
    var trailer = data.trailer;
  } catch (e) {
    var trailer = "";
  }
  try {
    var type = data.type;
  } catch (e) {
    var type = "";
  }
  try {
    var votes = data.votes;
  } catch (e) {
    var votes = "";
  }
  try {
    var year = " (" + data.year + ")";
  } catch (e) {
    var year = "";
  }
  var genres_text = getEntryOrEmpty(
    `[color=#FF8000][b]Genres[/b][/color]: {text}\n`,
    joinArrayOrEmpty(genres, ", ")
  );
  var title_text = getEntryOrEmpty(
    `[color=#FF8000][b]Title[/b][/color]: {text}\n`,
    title
  );
  var image = getEntryOrEmpty(
    `[center][url={url}][img]{text}[/img][/url][/center]\n`,
    image,
    permalink
  );
  var title = getEntryOrEmpty(
    `[center][size=200][b][url={url}]{text}${year}[/url] [/b][/size][/center]\n`,
    title,
    permalink
  );
  var rating = getEntryOrEmpty(
    `[center][b][size=110]{text} / 10[/size][/b] (based on ${votes} reviews)[/center]\n`,
    rating
  );
  var genres = getEntryOrEmpty(
    `[center][b][size=120]{text}[/size][/b][/center]\n`,
    joinArrayOrEmpty(genres, ", ")
  );
  var synopsis = getEntryOrEmpty(
    `[quote][center]{text}[/center][/quote]\n`,
    synopsis
  );
  var original_title = getEntryOrEmpty(
    `[color=#FF8000][b]Original Title[/b][/color]: {text}\n`,
    original_title
  );
  var alt_titles = getEntryOrEmpty(
    `[color=#FF8000][b]Alternate Titles[/b][/color]: {text}\n`,
    joinArrayOrEmpty(alt_titles, ", ")
  );
  var type = getEntryOrEmpty(
    `[color=#FF8000][b]Type[/b][/color]: {text}\n`,
    type
  );
  var runtime = getEntryOrEmpty(
    `[color=#FF8000][b]Runtime[/b][/color]: {text} minutes\n`,
    runtime
  );
  var episodes = getEntryOrEmpty(
    `[color=#FF8000][b]Episodes[/b][/color]: {text}\n`,
    episodes
  );
  var status = getEntryOrEmpty(
    `[color=#FF8000][b]Status[/b][/color]: {text}\n`,
    status
  );
  var aired_start = getEntryOrEmpty(
    `[color=#FF8000][b]Start[/b][/color]: {text}\n`,
    aired_start
  );
  var aired_end = getEntryOrEmpty(
    `[color=#FF8000][b]Finish[/b][/color]: {text}\n`,
    aired_end
  );
  var country = getEntryOrEmpty(
    `[color=#FF8000][b]Country[/b][/color]: {text}\n`,
    country
  );
  var language = getEntryOrEmpty(
    `[color=#FF8000][b]Language[/b][/color]: {text}\n`,
    language
  );
  // [url=https://links.snahp.it/xxxx][color=#FF0000]ZippyShare[/color][/url]
  // [/b][/hide]\n`
  var text =
    "" +
    image +
    "\n\n\n" +
    "" +
    title +
    "\n\n\n" +
    rating +
    "\n\n\n" +
    genres +
    "\n\n\n" +
    synopsis +
    "\n\n" +
    title_text +
    original_title +
    alt_titles +
    "\n" +
    type +
    language +
    runtime +
    episodes +
    "\n" +
    status +
    aired_start +
    aired_end +
    country +
    genres_text;
  return text;
};

Mydramalist.fillMydramalistPostMessage = function (entry) {
  $.get("/app.php/snahp/mdl/title/?id=" + entry.id, (resp) => {
    entry = JSON.parse(resp);
    var summary = Mydramalist.makeMydramalistTemplate(entry);
    var text = summary;
    $("#message").val(text);
  });
};

Mydramalist.updateMydramalistPosters = function (media) {
  $mydramalist_dialog = $("#mydramalist_dialog");
  $mydramalist_header = $("#mydramalist_header");
  $mydramalist_title = $("#mydramalist_title");
  $mydramalist_content = $("#mydramalist_poster_list").empty();
  var count = 0;
  for (var entry of media) {
    id = entry.id;
    name = entry.title;
    description = entry.description;
    releaseDate = entry.released;
    date = entry.year;
    img_url = entry.images.poster;
    type = entry.type;
    language = entry.language;
    try {
      img = img_url;
    } catch (e) {
      img = "";
    }
    $li = $("<li/>").addClass("img_li").appendTo($mydramalist_content);
    $imgDiv = $("<div/>").addClass("img_container").appendTo($li);
    $img = $("<img/>")
      .attr({
        id: "img-" + count,
        src: img,
      })
      .width("150")
      .height("225")
      .click(function (e) {
        target = e.target;
        var tid = $(target).attr("id");
        var match = tid.match(/img-(\d+)/);
        tid = parseInt(match[1], 10);
        var mydramalistid = $(target).attr("mydramalistid");
        Mydramalist.fillMydramalistPostMessage(media[tid]);
        $("#mydramalist_dialog").remove();
      })
      .appendTo($imgDiv);
    $type_txt = $("<div/>")
      .addClass("bottom-right")
      .html(
        `
            <span class="poster_image_text">
                <p>${name}</p>
                <p>${date}</p>
                <p>${type}</p>
                <p>${language}</p>
            <span>
        `
      )
      .appendTo($imgDiv);
    count++;
  }
  if (count == 0) {
    $notfound = $("<h>Not Found</h>").css({ "font-size": "200%" });
    $mydramalist_content.append($notfound);
  }
  $('<a href="#mydramalist_dialog" id="modalTriggerLink"></a>').appendTo(
    $("body")
  );
  setTimeout(function () {
    $("#modalTriggerLink")[0].click();
    $("#modalTriggerLink").remove();
    var match = /(.*)(#.*)/.exec(window.location.href);
    if (match && match[1]) {
      window.history.replaceState("", "", match[1]);
    }
  }, 100);
};

Mydramalist.mydramalist_dialog_template = `
<div id="mydramalist_dialog" class="twbs modalDialog mydramalist">
  <div class="twbs card document rounded">
    <div class="twbs card-body dialog rounded">
      <div class="twbs card-title">
        <h5 id="mydramalist_title"></h5>
      </div>
      <div class="twbs card dialog_content" id="mydramalist_content">
        <button id="close_btn" type="button" class="twbs dialog_close_btn">
          <span aria-hidden="true">&times;</span>
        </button>
        <div class="poster_list" id="mydramalist_poster_list">
        </div>
      </div>
    </div>
  </div>
</div>
`;

Mydramalist.handle_mydramalist = function (response, searchTerm) {
  $mydramalist_dialog = $(Mydramalist.mydramalist_dialog_template).appendTo(
    $("body")
  );
  $mydramalist_dialog = $("#mydramalist_dialog");
  $mydramalist_header = $("#mydramalist_header");
  $mydramalist_title = $("#mydramalist_title").text(
    `Results for "${searchTerm}"`
  );
  $mydramalist_content = $("#mydramalist_poster_list");
  $close_btn = $("#close_btn").click(function () {
    $("#mydramalist_dialog").remove();
  });
  Mydramalist.updateMydramalistPosters(result);
};

Mydramalist.startHandlingMydramalistAjax = function () {
  dict = {};
  $mydramalist_input = $("#mydramalist_input");
  var searchTerm = $mydramalist_input.val();
  var url = encodeURI(`/app.php/snahp/mdl/search/?title=${searchTerm}`);
  url = encodeURI(url);
  $ajax = $.ajax({
    url: url,
    dataType: "json",
  });
  $ajax.done(function (response) {
    result = JSON.parse(response);
    Mydramalist.handle_mydramalist(result, searchTerm);
  });
};

$(document).ready(function () {
  $("#mydramalist_input").keydown(function (event) {
    if (event.keyCode == 13) {
      event.preventDefault();
      Mydramalist.startHandlingMydramalistAjax();
    }
  });
});
