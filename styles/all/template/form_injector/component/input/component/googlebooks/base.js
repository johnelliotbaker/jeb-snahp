var Googlebooks = {};

Googlebooks.makeTemplate = function(data)
{
    data = data.volumeInfo;
    try { var thumbnail     = data.imageLinks.thumbnail.replace(/zoom=[0-9]+/ig, "zoom=3");} catch(e) { var thumbnail = "";};
    try { var title         = data['title'];} catch(e) { var title = "";};
    try { var subtitle      = data['subtitle'];} catch(e) { var subtitle = "";};
    try { var authors       = data['authors'];} catch(e) { var authors = "";};
    try { var averageRating = data['averageRating'];} catch(e) { var averageRating = "";};
    try { var url           = data['canonicalVolumeLink'];} catch(e) { var url = "";};
    try { var categories    = data['categories'];} catch(e) { var categories = "";};
    try { var description   = data['description'];} catch(e) { var description = "";};
    try { var language      = toTitleCase(data['language']);} catch(e) { var language = "";};
    try { var pageCount     = data['pageCount'];} catch(e) { var pageCount = "";};
    try {
        var previewLink   = data['previewLink'];
        match = /(.*)(&dq.*)/.exec(previewLink)
        if (match && Array.isArray(match) && match.length > 2)
            previewLink = match[1];
    } catch(e) { var previewLink = "";};
    try { var printType     = toTitleCase(data['printType']);} catch(e) { var printType = "";};
    try { var publishedDate = data['publishedDate'];} catch(e) { var publishedDate = "";};
    try { var publisher     = data['publisher'];} catch(e) { var publisher = "";};
    try { var ratingsCount  = data['ratingsCount'];} catch(e) { var ratingsCount = "";};
    var thumbnail     = getEntryOrEmpty(`[center][url={url}][img]{text}[/img][/url][/center]\n`, thumbnail, url);
    var title         = getEntryOrEmpty(`[center][size=200][b][url={url}]{text}[/url][/b][/size][/center]\n`, title, url);
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
        publishedDate + printType + language +
        publisher + pageCount + previewLink + '\n' +
        ddl + dlink;
    text = text.replace(/(<br>|<br\/>|<br \/>)/g, '');
    text = text.replace(/(\r?\n|\r){5,99}/g, '\n\n\n\n');
    return text;
}

Googlebooks.fillMessage = function(entry)
{
    var summary = Googlebooks.makeTemplate(entry);
    var text = summary;
    $('#message').val(text);
}

Googlebooks.updatePosters = function(media)
{
    $googlebooks_dialog = $("#googlebooks_dialog");
    $googlebooks_header = $("#googlebooks_header");
    $googlebooks_title  = $("#googlebooks_title");
    $googlebooks_content = $("#googlebooks_poster_list").empty();
    var count = 0;
    for (var entry of media)
    {
        var vinfo = entry.volumeInfo;
        title = vinfo.title;
        author =  vinfo.authors;
        pubDate =  vinfo.publishedDate;
        try {img = vinfo.imageLinks.thumbnail;} catch(e) {img = ''};
        $li = $("<li/>")
            .addClass("img_li")
            .appendTo($googlebooks_content);
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
                var googlebooksid = $(target).attr("googlebooksid");
                Googlebooks.fillMessage(media[tid]);
                $("#googlebooks_dialog").remove();
            })
            .appendTo($imgDiv)
        $type_txt = $("<div/>")
        .addClass("bottom-right")
            .html(`
            ${title}<br>
            ${pubDate}<br>
            ${author}<br>
        `)
        .appendTo($imgDiv);
        count++;
    }
    if (count == 0)
    {
        $notfound = $("<h>Not Found</h>").css({"font-size": "200%"});
        $googlebooks_content.append($notfound);
    }
    $('<a href="#googlebooks_dialog" id="modalTriggerLink"></a>').appendTo($("body"));
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

Googlebooks.googlebooks_dialog_template = `
<div id="googlebooks_dialog" class="twbs modalDialog googlebooks">
  <div class="twbs card document rounded">
    <div class="twbs card-body dialog rounded">
      <div class="twbs card-title">
        <h5 id="googlebooks_title"></h5>
      </div>
      <div class="twbs card dialog_content" id="googlebooks_content">
        <button id="close_btn" type="button" class="twbs dialog_close_btn">
          <span aria-hidden="true">&times;</span>
        </button>
        <div class="twbs card-group dialog_top_menu" id="googlebooks_top_filter">
          <div class="twbs card text-center">
            <div class="twbs card-body">
              <div class="twbs row">
                <div class="twbs card col-12">
                  <div class="modal-menu">
                    <input type="checkbox" id="cb_show_googlebooks_enable_sort" value="0">
                    <label class="checkbox_label" for="cb_show_googlebooks_enable_sort">Sort By Date</label>
                    <input type="checkbox" id="cb_show_googlebooks_sort_asc" value="1" checked>
                    <label class="checkbox_label" for="cb_show_googlebooks_sort_asc">Newest to Oldest</label>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="poster_list" id="googlebooks_poster_list">
        </div>
      </div>
    </div>
  </div>
</div>
`;

Googlebooks.filterGooglebooksMedia = function(media)
{
    var aType = [];
    aType.push("BOOK");
    var selectedMedia = [];
    for (var i in media)
    {
        var entry = media[i].volumeInfo;
        var pubDate = entry['publishedDate'];
        var match = /(\d{4})/.exec(pubDate);
        media[i].date = Array.isArray(match) ? parseInt(match[0]) : 0;
        var type = entry['printType'];
        if (aType.includes(type))
        {
            selectedMedia.push(media[i]);
        }
    }
    if ($("#cb_show_googlebooks_enable_sort").prop("checked"))
    {
        selectedMedia = selectedMedia.sort(function(a, b){
            if ($("#cb_show_googlebooks_sort_asc").prop("checked"))
                return -a.date+b.date;
            return a.date-b.date;
        });
    }
    return selectedMedia;
}

Googlebooks.handle_googlebooks = function(response, searchTerm)
{
    $googlebooks_dialog = $(Googlebooks.googlebooks_dialog_template).appendTo($("body"));
    $googlebooks_dialog = $("#googlebooks_dialog");
    $googlebooks_header = $("#googlebooks_header");
    $googlebooks_title  = $("#googlebooks_title").text(`Results for "${searchTerm}"`);
    $googlebooks_content = $("#googlebooks_poster_list");
    $close_btn = $("#close_btn")
        .click(function(){
            $('#googlebooks_dialog').remove();
        })
    $("[id^=cb_show_googlebooks]").change(function(event){
            var selectedMedia = Googlebooks.filterGooglebooksMedia(media);
            Googlebooks.updatePosters(selectedMedia);
        });
    var media = response.items;
    var selectedMedia = Googlebooks.filterGooglebooksMedia(media);
    Googlebooks.updatePosters(selectedMedia);
}

Googlebooks.addSearchQualifier = function(url, dict)
{
    for (var key in dict)
    {
        val = dict[key];
        if (val)
        {
            switch (key)
            {
                case "title":
                    url += "+intitle:"
                    break;
                case "author":
                    url += "+inauthor:"
                    break;
                case "isbn":
                    url += "+isbn:"
                    break;
                case "order":
                    url += "&orderBy="
            }
            url += val;
        }
    }
    return url;
}

Googlebooks.startHandlingGooglebooksAjax = function()
{
    dict ={};
    $googlebooks_input = $("#googlebooks_input");
    $googlebooks_input_author = $("#googlebooks_input_author");
    var author = $googlebooks_input_author.val();
    var searchTerm = $googlebooks_input.val();
    var regex = /author:"([^"]*)"/;
    var match = regex.exec(searchTerm)
    searchTerm = searchTerm.replace(regex, "");
    if (Array.isArray(match) && match[1])
        dict['author'] = match[1];
    var url = `https://www.googleapis.com/books/v1/volumes?&maxResults=40&q=${searchTerm}`;
    if (author)
    {
        url += `+inauthor:${author}`;
    }
    $ajax = $.ajax(
        {
            url: url,
            method: 'get',
        }
    );
    $ajax.done(function(response){
        Googlebooks.handle_googlebooks(response, searchTerm);
    });
}

$(document).ready(function() {
    $("#googlebooks_input_author").keydown(function(event){
        if(event.keyCode == 13) {
            event.preventDefault();
            Googlebooks.startHandlingGooglebooksAjax();
        }
    });
    $("#googlebooks_input").keydown(function(event){
        if(event.keyCode == 13) {
            event.preventDefault();
            Googlebooks.startHandlingGooglebooksAjax();
        }
    });
});
