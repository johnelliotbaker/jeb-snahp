function getEntryOrEmpty(template, strn)
{
    try
    {
        if (strn)
        {
            return template.replace('{{}}', strn);
        }
        else
        {
            return "";
        }
    }
    catch(err)
    {
        return "";
    }

}

function makeAnilistTemplate(data)
{
    var titleRomaji  = getEntryOrEmpty(`[center][size=150][color=#FF0000][b]{{}}[/b][/color][/size][/center]\n`, data['title']['romaji']);
    var img          = getEntryOrEmpty(`[center][img width="300"]{{}}[/img][/center]\n`, data['coverImage']['large']);
    var year         = getEntryOrEmpty(`[center][size=150][color=#000000][b]({{}})[/b][/color][/size][/center]\n`, data['startDate']['year']);
    var titleEnglish = getEntryOrEmpty(`[center][size=150][color=#FF0000][b]{{}}[/b][/color][/size][/center]\n`, data['title']['english']);
    var titleNative  = getEntryOrEmpty(`[center][size=150][color=#FF0000][b]{{}}[/b][/color][/size][/center]\n`, data['title']['native']);
    var director     = getEntryOrEmpty(`[color=#FF8000][b]Director[/b][/color]: {{}}\n`, data['Director']);
    var actors       = getEntryOrEmpty(`[color=#FF8000][b]Stars[/b][/color]: {{}}\n`, data['Actors']);
    var runtime      = getEntryOrEmpty(`[color=#FF8000][b]Runtime[/b][/color]: {{}} minutes\n`, data['duration']);
    var genre        = getEntryOrEmpty(`[color=#FF8000][b]Genre[/b][/color]: {{}}\n`, data['genres'].join(', '));
    var rating       = getEntryOrEmpty(`[color=#FF8000][b]Rating[/b][/color]: {{}}\n`, data['averageScore']);
    var votes        = getEntryOrEmpty(`[color=#FF8000][b]Votes[/b][/color]: {{}}\n`, numberWithCommas(data['popularity']));
    var reldate      = getEntryOrEmpty(`[color=#FF8000][b]Release Date[/b][/color]: {{}}\n`, data['startDate']['year']);
    var vrating      = getEntryOrEmpty(`[color=#FF8000][b]Viewer Rating (TV/MPAA)[/b][/color]: {{}}\n`, data['Rated']);
    var summary      = getEntryOrEmpty(`[color=#FF8000][b]Summary[/b][/color]: [i]{{}}[/i]\n`, data['description']);
    var links        = `[color=#FF8000][b]Links[/b][/color]: [b]`;
    var ddl          = `[color=#0000FF][b]Direct Download Links[/color][/b]: \n`;
    var dlink        = `[hide][b][url=https://links.snahp.it/xxxx][color=#FF0000]MEGA[/color][/url]
[url=https://links.snahp.it/xxxx][color=#FF0000]ZippyShare[/color][/url]
[url=https://snahp.it/?s=tt1270797][color=#FF0000]ZippyShare[/color][/url]
[/b][/hide]\n`
    var text = img + '\n';
    if (titleNative && titleNative != 'null') text += titleNative + '\n';
    if (titleRomaji && titleRomaji != 'null') text += titleRomaji + '\n';
    if (titleEnglish && titleEnglish != 'null') text += titleEnglish + '\n';
    text += year + '\n\n' +
    runtime + genre + '\n' +
    rating + votes +'\n' +
    reldate + '\n' +
    summary + '\n' +
    ddl + dlink;
    return text;
}


function numberWithCommas(x) {
    return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}


function fillAnilistPostMessage(entry)
{
    var summary = makeAnilistTemplate(entry);
    var text = summary;
    $('#message').val(text);
}


var anilist_dialog_template = `
<!-- Modal -->
<div class="twbs modal fade" id="anilist_dialog" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="twbs modal-dialog modal-xl" role="document">
    <div class="twbs modal-content">
      <div class="twbs modal-header">
        <h5 class="twbs modal-title" id="anilist_title"></h5>
        <button id="close_btn" type="button" class="twbs close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="twbs modal-body" id="anilist_content">
      <div class="twbs modal-body" id="anilist_top_filter">
	  <div class="twbs form-check form-check-inline">
	    <input class="twbs form-check-input" type="checkbox" id="cb_show_anime" value="1" checked>
	    <label class="twbs form-check-label" for="cb_show_anime">Anime</label>
	    <input class="twbs form-check-input" type="checkbox" id="cb_show_manga" value="1" checked>
	    <label class="twbs form-check-label" for="cb_show_manga">Manga</label>
	  </div>
      </div>
      <div class="twbs modal-body" id="anilist_poster_list">
      </div>
      </div>
      <div class="twbs modal-footer">
      </div>
    </div>
  </div>
</div>

`;

function updatePosters(media)
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
        $imgDiv = $("<div/>")
            .addClass("img_container")
            .appendTo($li);
        $img = $("<img/>")
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
                fillAnilistPostMessage(media[tid]);
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
    $("#anilist_dialog").modal('handleUpdate');
}

function filterMedia(media)
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



function handle_anilist(response, searchTerm)
{
    $anilist_dialog = $(anilist_dialog_template).appendTo($("body"));
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
            var selectedMedia = filterMedia(media);
            updatePosters(selectedMedia);
        });
    $("#cb_show_anime").change(function(event){
            var selectedMedia = filterMedia(media);
            updatePosters(selectedMedia);
        });

    var media = response['data']['Page']['media'];
    var selectedMedia = filterMedia(media);
    updatePosters(selectedMedia);
    $("#anilist_dialog").modal('show');
    $('.modal').toggleClass('is-visible');
    console.log(jQuery.fn.jquery);
}


function startHandlingAnilistAjax()
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
        handle_anilist(response, searchTerm);
    });
}

phpbb.addAjaxCallback('snahp.anilistCallback', startHandlingAnilistAjax);
$(document).ready(function() {
    $("#anilist_input").keydown(function(event){
        if(event.keyCode == 13) {
            event.preventDefault();
            startHandlingAnilistAjax();
        }
    });
});
