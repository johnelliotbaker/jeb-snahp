API_KEY = ['3c07135c4058cc5bdf102c5c7e00673219d97723',
    ['3c933606426b571022d5eac83821e700fab93929']
];

function select_key(aKey)
{
    var maxi = 1; var mini = 0;
    var i = Math.floor(Math.random() * (maxi - mini + 1)) + mini;
    return aKey[i];
}

function makeGamespotTemplate(data)
{
    // console.log(data.image);
    try { var thumbnail     = data.image.original;} catch { var thumbnail = "";};
    try { var title         = data.name;} catch { var title = "";};
    try { var subtitle      = data['subtitle'];} catch { var subtitle = "";};
    try { var averageRating = data['averageRating'];} catch { var averageRating = "";};
    try { var url           = data.site_detail_url;} catch { var url = "";};
    try { var categories = []; for (var genre of data.genres) { categories.push(genre['name']); }
    } catch { var categories = "";};
    try { var description   = data.description;} catch { var description = "";};
    try {
        var previewLink   = data.videos_api_url;
        match = /(.*)(&dq.*)/.exec(previewLink)
        if (match && Array.isArray(match) && match.length > 2)
            previewLink = match[1];
    } catch { var previewLink = "";};
    try { var publishedDate = data.date;} catch { var publishedDate = "";};
    try { var publisher     = data['publisher'];} catch { var publisher = "";};
    try { var ratingsCount  = data['ratingsCount'];} catch { var ratingsCount = "";};

    var thumbnail     = getEntryOrEmpty(`[center][url={url}][img]{text}[/img][/url][/center]\n`, thumbnail, url);
    var title         = getEntryOrEmpty(`[center][size=200][b][url={url}]{text} (${publishedDate})[/url] [/b][/size][/center]\n`, title, url);
    var subtitle      = getEntryOrEmpty(`[center][size=120][b]{text}[/b][/size][/center]\n`, subtitle);
    var authors       = getEntryOrEmpty(`[center][b][size=80]by[/size]\n\n[size=180]{text}[/size][/b][/center]\n`, joinArrayOrEmpty(authors, ', '));
    var averageRating = getEntryOrEmpty(`[center][b][size=110]{text} / 5[/size][/b] (based on ${ratingsCount} reviews)[/center]\n`, averageRating);
    var categories    = getEntryOrEmpty(`[center][b][size=140]{text}[/size][/b][/center]\n`, joinArrayOrEmpty(categories, ', '));
    var description   = getEntryOrEmpty(`[quote][center]{text}[/center][/quote]\n`, description);

    var publishedDate = getEntryOrEmpty(`[color=#FF8000][b]Published Date[/b][/color]: {text}\n`, publishedDate);
    var printType     = getEntryOrEmpty(`[color=#FF8000][b]Print Type[/b][/color]: {text}\n`, printType);
    var language      = getEntryOrEmpty(`[color=#FF8000][b]Language[/b][/color]: {text}\n`, language);

    var publisher     = getEntryOrEmpty(`[color=#FF8000][b]Publisher[/b][/color]: {text}\n`, publisher);
    var pageCount     = getEntryOrEmpty(`[color=#FF8000][b]Page Count[/b][/color]: {text}\n`, pageCount);
    var previewLink   = getEntryOrEmpty(`[color=#FF8000][b]Preview[/b][/color]: [url={url}]{text}[/url]\n`, "Google Books", previewLink);
    var ratingsCount  = getEntryOrEmpty(`[color=#FF8000][b]Ratings Count[/b][/color]: {text}\n`, ratingsCount);

    var ddl           = `[color=#0000FF][b]Direct Download Links[/b][/color]: \n`;
    var dlink         = `[hide][b][url=https://links.snahp.it/xxxx][color=#FF0000]MEGA[/color][/url]
[url=https://links.snahp.it/xxxx][color=#FF0000]ZippyShare[/color][/url]
[/b][/hide]\n`
    var text = '' + 
        thumbnail + '\n\n\n' +
        title + subtitle +'\n\n\n' +
        authors +  '\n\n\n' +
        averageRating + '\n\n\n' +
        categories + '\n\n' +
        description + '\n\n' +
        // publishedDate + printType + language +
        // publisher + pageCount + previewLink + '\n' +
        ddl + dlink;
    text = text.replace(/(<br>|<br\/>|<br \/>)/g, '');
    text = text.replace(/(\r?\n|\r){5,99}/g, '\n\n\n\n');
    return text;
}

function fillGamespotPostMessage(entry)
{
    // var reviewsUrl = `https://www.gamespot.com/api/releases/?api_key=${API_KEY}&format=jsonp&limit=5&filter=name:fallout`;
    // console.log(reviewsUrl);
    // $ajax = $.ajax( { url: reviewsUrl, dataType: 'jsonp', jsonp: 'json_callback', }).
    //     done(resp => {
    //         console.log(resp);
    //
    //     });
    var summary = makeGamespotTemplate(entry);
    var text = summary;
    $('#message').val(text);
}

function updateGamespotPosters(media)
{
    $gamespot_dialog = $("#gamespot_dialog");
    $gamespot_header = $("#gamespot_header");
    $gamespot_title  = $("#gamespot_title");
    $gamespot_content = $("#gamespot_poster_list").empty();
    var count = 0;
    for (var entry of media)
    {
        name = entry.name;
        description = entry.description;
        releaseDate =  entry.releaseDate;
        date = entry.date;
        try {img = entry.image.original;} catch {img = ''};

        $li = $("<li/>")
            .addClass("img_li")
            .appendTo($gamespot_content);
        $imgDiv = $('<div/>')
            .addClass('img_container')
            .appendTo($li);
        $img = $('<img/>')
            .attr({
                "id": "img-" + count,
                "src": img,
                })
            .width("150")
            .height("225")
            .click( function(e) {
                target = e.target;
                var tid = $(target).attr("id");
                var match = tid.match(/img-(\d+)/);
                tid = parseInt(match[1], 10);
                var gamespotid = $(target).attr("gamespotid");
                fillGamespotPostMessage(media[tid]);
                $("#gamespot_dialog").remove();
            })
            .appendTo($imgDiv)
        $type_txt = $("<div/>")
        .addClass("bottom-right")
            .html(`
            ${name}<br>
            ${date}<br>
        `)
        .appendTo($imgDiv);
        count++;
    }
    if (count == 0)
    {
        $notfound = $("<h>Not Found</h>").css({"font-size": "200%"});
        $gamespot_content.append($notfound);
    }
    $('<a href="#gamespot_dialog" id="modalTriggerLink"></a>').appendTo($("body"));
    setTimeout(function(){
        $("#modalTriggerLink")[0].click();
        $("#modalTriggerLink").remove();
        var match = /(.*)(#.*)/.exec(window.location.href);
        if (match && match[1])
        {
            window.history.replaceState("", "", match[1]);
        }
    }, 100);
}


var gamespot_dialog_template = `
<div id="gamespot_dialog" class="twbs modalDialog gamespot">
  <div class="twbs card document rounded">
    <div class="twbs card-body dialog rounded">
      <div class="twbs card-title">
        <h5 id="gamespot_title"></h5>
      </div>
      <div class="twbs card dialog_content" id="gamespot_content">
        <button id="close_btn" type="button" class="twbs dialog_close_btn">
          <span aria-hidden="true">&times;</span>
        </button>
        <div class="twbs card-group dialog_top_menu" id="gamespot_top_filter">
          <div class="twbs card text-center">
            <div class="twbs card-body">
              <div class="twbs row">
                <div class="twbs card col-12">
                  <div class="modal-menu">
                    <input type="checkbox" id="cb_show_gamespot_enable_sort" value="0">
                    <label class="checkbox_label" for="cb_show_gamespot_enable_sort">Sort By Date</label>
                    <input type="checkbox" id="cb_show_gamespot_sort_asc" value="1" checked>
                    <label class="checkbox_label" for="cb_show_gamespot_sort_asc">Newest to Oldest</label>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="poster_list" id="gamespot_poster_list">
        </div>
      </div>
    </div>
  </div>
</div>
`;

function filterGamespotMedia(media)
{
    var aType = [];
    var selectedMedia = [];
    for (var i in media)
    {
        var entry = media[i];
        var releaseDate = entry['release_date'];
        var match = /(\d{4})/.exec(releaseDate);
        media[i].date = Array.isArray(match) ? parseInt(match[0]) : 0;
        var type = entry['printType'];
        selectedMedia.push(media[i]);
    }
    if ($("#cb_show_gamespot_enable_sort").prop("checked"))
    {
        selectedMedia = selectedMedia.sort(function(a, b){
            if ($("#cb_show_gamespot_sort_asc").prop("checked"))
                return -a.date+b.date;
            return a.date-b.date;
        });

    }
    return selectedMedia;
}

function handle_gamespot(response, searchTerm)
{
    $gamespot_dialog = $(gamespot_dialog_template).appendTo($("body"));
    $gamespot_dialog = $("#gamespot_dialog");
    $gamespot_header = $("#gamespot_header");
    $gamespot_title  = $("#gamespot_title").text(`Results for "${searchTerm}"`);
    $gamespot_content = $("#gamespot_poster_list");
    $close_btn = $("#close_btn")
        .click(function(){
            $('#gamespot_dialog').remove();
        })
    $("[id^=cb_show_gamespot]").change(function(event){
            var selectedMedia = filterGamespotMedia(media);
            updateGamespotPosters(selectedMedia);
        });
    var media = response.results;
    var selectedMedia = filterGamespotMedia(media);
    updateGamespotPosters(selectedMedia);
}

function startHandlingGamespotAjax()
{
    dict ={};
    $gamespot_input = $("#gamespot_input");
    var searchTerm = $gamespot_input.val();
    // TODO: Run this through url encode
    var mode = 'games';
    var fieldname = 'name'
    // var mode = 'reviews';
    // var fieldname = 'title'
    var limit = 40;
    var dataFormat = 'jsonp';
    var key = select_key(API_KEY)
    var url = `https://www.gamespot.com/api/${mode}/?api_key=${key}&format=${dataFormat}&limit=${limit}&filter=${fieldname}:${searchTerm}`;
    url = encodeURI(url);
    $ajax = $.ajax(
        {
            url: url,
            dataType: 'jsonp',
            jsonp: 'json_callback',
        }
    );
    $ajax.done(function(response){
        handle_gamespot(response, searchTerm);
    });
}


phpbb.addAjaxCallback('snahp.gamespotCallback', startHandlingGamespotAjax);
$(document).ready(function() {
    $("#gamespot_input").keydown(function(event){
        if(event.keyCode == 13) {
            event.preventDefault();
            startHandlingGamespotAjax();
        }
    });
});
