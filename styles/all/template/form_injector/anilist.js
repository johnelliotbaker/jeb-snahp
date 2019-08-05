var Anilist = {};

Anilist.getEntryOrEmpty = function(template, text, url=0)
{
    if (text) { template = template.replace('{text}', text);}
    else      { template = "";}
    if (url)  { template = template.replace('{url}', url);}
    return template;
}

Anilist.getEndDateOrEmpty = function(template, endDate, url=0)
{
    var year  = endDate['year'];
    var month = endDate['month'];
    var day   = endDate['day'];
    var strn  = "";
    if (year)  { strn += year;}
    if (month) { strn += '/' + month;}
    if (day)   { strn += '/' + day;}
    return Anilist.getEntryOrEmpty(template, strn, url);
}

Anilist.getRatingOrEmpty = function(template, rating, url=0)
{
    if (rating > 0)
    {
        return Anilist.getEntryOrEmpty(template, (rating/10).toFixed(1), url);
    }
    return "";
}

Anilist.toTitleCase = function(str) {
    // https://stackoverflow.com/questions/4878756/how-to-capitalize-first-letter-of-each-word-like-a-2-word-city
    if (str)
    {
        return str.replace(/\w\S*/g, function(txt){
            return txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase();
        });
    }
    return "";
}

Anilist.makeAnilistTemplate = function(data)
{
    var type          = data['type'];
    var id            = data['id'];
    var url           = data['siteUrl'];
    var img           = Anilist.getEntryOrEmpty(`[center][img width="300"]{text}[/img][/center]\n`, data['coverImage']['large']);
    var bannerImage   = data['bannerImage'] ? Anilist.getEntryOrEmpty(`[center][img width="300"]{text}[/img][/center]\n`, data['bannerImage']) : "";
    var year          = data['startDate']['year'] ? ' (' + data['startDate']['year'] + ')' : "";
    var titleNative   = Anilist.getEntryOrEmpty(`[center][size=200][b][url={url}]{text}${year}[/url][/b][/size][/center]\n`, data['title']['native'], url);
    var titleRomaji   = Anilist.getEntryOrEmpty(`[center][size=100][b]{text}[/b][/size][/center]\n`, data['title']['romaji']);
    var titleEnglish  = Anilist.getEntryOrEmpty(`[center][size=100][b]{text}[/b][/size][/center]\n`, data['title']['english']);
    var rating        = Anilist.getRatingOrEmpty(`[center][size=150][b]{text} / 10[/b][/size][/center]\n`, data['averageScore']);
    var genre         = Anilist.getEntryOrEmpty(`[center][b][size=120]{text}[/size][/b][/center]\n`, data['genres'].join(', '));
    var summary       = Anilist.getEntryOrEmpty(`[quote][center]{text}[/center][/quote]\n`, data['description']);
    var volumes       = Anilist.getEntryOrEmpty(`[color=#FF8000][b]Volumes[/b][/color]: {text}\n`, data['volumes']);
    var format        = Anilist.getEntryOrEmpty(`[color=#FF8000][b]Format[/b][/color]: {text}\n`, Anilist.toTitleCase(data['format']));
    var trailer       = "";
    try { var trailer = '{snahp}{youtube}' + data['trailer']['id'] + '{/youtube}{/snahp}' } catch(e) {};
    var episodes      = Anilist.getEntryOrEmpty(`[color=#FF8000][b]Episodes[/b][/color]: {text}\n`, data['episodes']);
    var endDate       = Anilist.getEndDateOrEmpty(`[color=#FF8000][b]End Date[/b][/color]: {text}\n`, data['endDate']);
    var chapters      = Anilist.getEntryOrEmpty(`[color=#FF8000][b]Chapters[/b][/color]: {text}\n`, data['chapters']);
    var runtime       = Anilist.getEntryOrEmpty(`[color=#FF8000][b]Runtime[/b][/color]: {text} minutes\n`, data['duration']);
    var votes         = Anilist.getEntryOrEmpty(`[color=#FF8000][b]Votes[/b][/color]: {text}\n`, numberWithCommas(data['popularity']));
    var links         = `[color=#FF8000][b]Links[/b][/color]: [b]`;
    var ddl           = `[color=#0000FF][b]Direct Download Links[/b][/color]: \n`;
    var dlink         = `[hide][b][url=https://links.snahp.it/xxxx][color=#FF0000]MEGA[/color][/url]
[url=https://links.snahp.it/xxxx][color=#FF0000]ZippyShare[/color][/url]
[/b][/hide]\n`
    var text = '' + 
        bannerImage + '\n\n\n' + 
        img + '\n\n\n';
        if (titleNative && titleNative != 'null') text += titleNative + '\n';
        if (titleRomaji && titleRomaji != 'null') text += titleRomaji + '\n';
        if (titleEnglish && titleEnglish != 'null') text += titleEnglish + '\n';
    text += '\n\n' + rating + '\n\n\n' +
        genre + '\n\n' +
        summary + '\n\n' +
        format + runtime + episodes + volumes + chapters + endDate +
        votes + trailer + '\n' +
        ddl + dlink;
    text = text.replace(/(<br>|<br\/>|<br \/>)/g, '');
    text = text.replace(/(\r?\n|\r){5,99}/g, '\n\n\n\n');
    return text;
}

Anilist.fillAnilistPostMessage = function(entry)
{
    var summary = Anilist.makeAnilistTemplate(entry);
    var text = summary;
    $('#message').val(text);
}

Anilist.updatePosters = function(media)
{
    $anilist_dialog = $("#anilist_dialog");
    $anilist_header = $("#anilist_header");
    $anilist_title  = $("#anilist_title");
    $anilist_content = $("#anilist_poster_list").empty();
    var count = 0;
    for (var entry of media)
    {
        $li = $("<li/>")
            .addClass("img_li")
            .appendTo($anilist_content);
        $imgDiv = $('<div/>')
            .addClass('img_container')
            .appendTo($li);
        $img = $('<img/>')
            .attr({
                "id": "img-" + count,
                "src": entry["coverImage"]["large"],
                })
            .width("150")
            .height("225")
            .click( function(e) {
                target = e.target;
                var tid = $(target).attr("id");
                var match = tid.match(/img-(\d+)/);
                tid = parseInt(match[1], 10);
                var anilistid = $(target).attr("anilistid");
                Anilist.fillAnilistPostMessage(media[tid]);
                $("#anilist_dialog").remove();
                // $('#anilist_dialog').modal("hide");
                // $('.modal').remove();
            })
            .appendTo($imgDiv)
        $type_txt = $("<div/>")
        .addClass("bottom-right")
            .html(`
            ${entry["title"]['romaji']}<br>
            ${entry["startDate"]["year"]}<br>
            ${entry["type"]}
        `)
        .appendTo($imgDiv);
        count++;
    }
    if (count == 0)
    {
        $notfound = $("<h>Not Found</h>").css({"font-size": "200%"});
        $anilist_content.append($notfound);
    }
    $('<a href="#anilist_dialog" id="modalTriggerLink"></a>').appendTo($("body"));
    setTimeout(function(){
        $("#modalTriggerLink")[0].click();
        $("#modalTriggerLink").remove();
        var match = /(.*)(#.*)/.exec(window.location.href);
        if (match && match[1])
        {
            window.history.replaceState("", "", match[1]);
        }
    }, 100);
    // $("#anilist_dialog").css({ "opacity": "1", "pointer-events": "auto" });
}

Anilist.filterAnilistMedia = function(media)
{
    var aType = [];
    if ($("#cb_show_anime").prop("checked"))
    { aType.push("ANIME") }
    if ($("#cb_show_manga").prop("checked"))
    { aType.push("MANGA") }
    var selectedMedia = [];
    for (var i in media)
    {
        var entry = media[i];
        var type = entry['type'];
        if (aType.includes(type))
        {
            selectedMedia.push(entry);
        }
    }
    return selectedMedia;
}

Anilist.anilist_dialog_template = `
<div id="anilist_dialog" class="twbs modalDialog anilist">
  <div class="twbs card document rounded">
    <div class="twbs card-body dialog rounded">
      <div class="twbs card-title">
        <h5 id="anilist_title"></h5>
      </div>
      <div class="twbs card dialog_content" id="anilist_content">
        <button id="close_btn" type="button" class="twbs dialog_close_btn">
          <span aria-hidden="true">&times;</span>
        </button>
        <div class="twbs card text-center">
          <div class="twbs card-body">
            <div class="twbs row">
              <div class="twbs card col-12">
                <div class="modal-menu">
                  <input type="checkbox" id="cb_show_anime" value="1" checked>
                  <label class="checkbox_label" for="cb_show_anime">Anime</label>
                  <input type="checkbox" id="cb_show_manga" value="1" checked>
                  <label class="checkbox_label" for="cb_show_manga">Manga</label>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div id="anilist_poster_list"></div>
      </div>
    </div>
  </div>
</div>
`;

Anilist.handle_anilist = function(response, searchTerm)
{
    $anilist_dialog = $(Anilist.anilist_dialog_template).appendTo($("body"));
    $anilist_dialog = $("#anilist_dialog");
    $anilist_header = $("#anilist_header");
    $anilist_title  = $("#anilist_title").text(`Results for "${searchTerm}"`);
    $anilist_content = $("#anilist_poster_list");
    $close_btn = $("#close_btn")
        .click(function(){
            $('#anilist_dialog').remove();
            // $('#anilist_dialog').modal("hide");
        })
    $("#cb_show_manga").change(function(event){
            var selectedMedia = Anilist.filterAnilistMedia(media);
            Anilist.updatePosters(selectedMedia);
        });
    $("#cb_show_anime").change(function(event){
            var selectedMedia = Anilist.filterAnilistMedia(media);
            Anilist.updatePosters(selectedMedia);
        });
    var media = response['data']['Page']['media'];
    var selectedMedia = Anilist.filterAnilistMedia(media);
    Anilist.updatePosters(selectedMedia);
}

Anilist.startHandlingAnilistAjax = function()
{
    $anilist_input = $("#anilist_input");
    var searchTerm = $anilist_input.val();
    var url = 'https://graphql.anilist.co';
    JSON.stringify({
        query: query,
        variables: variables
    })
    var query = `
    query ($searchTerm:String){
      Page{
        media(search: $searchTerm) {
          id,
          title {
            romaji
            english
            native
          },
          type,
          startDate {
            year
          },
          endDate {
            year
            month
            day
          }
          episodes,
          chapters,
          duration,
          averageScore,
          popularity,
          description,
          coverImage {
            large
          },
          genres,
          bannerImage,
          trailer {
            id
          },
          siteUrl,
          volumes,
          format,
        }
      }
    }
    `;
    // Define our query variables and values that will be used in the query request
    var variables = {
      "searchTerm": searchTerm,
    };
    $ajax = $.ajax(
        {
            url: url,
            method: 'POST',
            data: JSON.stringify({ query: query, variables: variables }),
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', },
        }
    );
    $ajax.done(function(response){
        Anilist.handle_anilist(response, searchTerm);
    });
}

phpbb.addAjaxCallback('snahp.anilistCallback', Anilist.startHandlingAnilistAjax);
$(document).ready(function() {
    $("#anilist_input").keydown(function(event){
        if(event.keyCode == 13) {
            event.preventDefault();
            Anilist.startHandlingAnilistAjax();
        }
    });
});
