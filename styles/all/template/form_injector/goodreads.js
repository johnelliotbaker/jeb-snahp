var Goodreads = {};

Goodreads.makeTemplate = function(data)
{
    console.log(data);
    try { var thumbnail     = data.image_url.replace(/SX[0-9]+/ig,"SX2000");} catch(e) { var thumbnail = "";};
    try { var title         = data.title;} catch(e) { var title = "";};
    try { var authors       = data['authors'];} catch(e) { var authors = "";};
    try { var averageRating = data.average_rating;} catch(e) { var averageRating = "";};
    try { var url           = data.link;} catch(e) { var url = "";};
    try { var categories    = data['categories'];} catch(e) { var categories = "";};
    try { var description   = data.description.replace(/(<([^>]+)>)/ig," ");} catch(e) { var description = "";};
    try { var language      = toTitleCase(data.language_code);} catch(e) { var language = "";};
    try { var pageCount     = data.num_pages;} catch(e) { var pageCount = "";};
    try { var printType     = toTitleCase(data['printType']);} catch(e) { var printType = "";};
    try { var publishedDate = data.publication_year;} catch(e) { var publishedDate = "";};
    try { var publisher     = data.publisher;} catch(e) { var publisher = "";};
    try { var ratingsCount  = data.ratings_count;} catch(e) { var ratingsCount = "";};
    var thumbnail     = getEntryOrEmpty(`[center][url={url}][img]{text}[/img][/url][/center]\n`, thumbnail, url);
    var title         = getEntryOrEmpty(`[center][size=200][b][url={url}]{text}[/url][/b][/size][/center]\n`, title, url);
    var authors       = getEntryOrEmpty(`[center][b][size=80]by[/size]\n\n[size=180]{text}[/size][/b][/center]\n`, joinArrayOrEmpty(authors, ', '));
    var averageRating = getEntryOrEmpty(`[center][b][size=110]{text} / 5[/size][/b] (based on ${ratingsCount} reviews)[/center]\n`, averageRating);
    var categories    = getEntryOrEmpty(`[center][b][size=140]{text}[/size][/b][/center]\n`, joinArrayOrEmpty(categories, ', '));
    var description   = getEntryOrEmpty(`[quote][center]{text}[/center][/quote]\n`, description);
    var publishedDate = getEntryOrEmpty(`[color=#FF8000][b]Published Date[/b][/color]: {text}\n`, publishedDate);
    var printType     = getEntryOrEmpty(`[color=#FF8000][b]Print Type[/b][/color]: {text}\n`, printType);
    var language      = getEntryOrEmpty(`[color=#FF8000][b]Language[/b][/color]: {text}\n`, language);
    var publisher     = getEntryOrEmpty(`[color=#FF8000][b]Publisher[/b][/color]: {text}\n`, publisher);
    var pageCount     = getEntryOrEmpty(`[color=#FF8000][b]Page Count[/b][/color]: {text}\n`, pageCount);
    var ratingsCount  = getEntryOrEmpty(`[color=#FF8000][b]Ratings Count[/b][/color]: {text}\n`, ratingsCount);
    var ddl           = `[color=#0000FF][b]Direct Download Links[/b][/color]: \n`;
    var dlink         = `[hide][b][url=https://links.snahp.it/xxxx][color=#FF0000]MEGA[/color][/url]
[url=https://links.snahp.it/xxxx][color=#FF0000]ZippyShare[/color][/url]
[/b][/hide]\n`
    var text = '' + 
        thumbnail + '\n\n\n' +
        title + '\n\n\n' +
        authors +  '\n\n\n' +
        averageRating + '\n\n\n' +
        description + '\n\n' +
        publishedDate + language +
        ddl + dlink;
    text = text.replace(/(<br>|<br\/>|<br \/>)/g, '');
    text = text.replace(/(\r?\n|\r){5,99}/g, '\n\n\n\n');
    return text;
}

Goodreads.fillMessage = function(entry)
{
    var book_id = entry.best_book.id;
    var url = 'http://192.168.2.12:888/app.php/snahp/api_proxy/goodreads/?cmd=book&bid=' + book_id;
    $.get(url).done((resp)=>{
        var summary = Goodreads.makeTemplate(resp);
        var text = summary;
        $('#message').val(text);
    });
}

Goodreads.updatePosters = function(media)
{
    $goodreads_dialog = $("#goodreads_dialog");
    $goodreads_header = $("#goodreads_header");
    $goodreads_title  = $("#goodreads_title");
    $goodreads_content = $("#goodreads_poster_list").empty();
    var count = 0;
    for (var entry of media)
    {
        title = entry.best_book.title;
        author =  entry.best_book.author.name;
        pubDate =  entry.original_publication_year;
        img = entry.best_book.image_url;
        $li = $("<li/>")
            .addClass("img_li")
            .appendTo($goodreads_content);
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
                var goodreadsid = $(target).attr("goodreadsid");
                Goodreads.fillMessage(media[tid]);
                $("#goodreads_dialog").remove();
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
        $goodreads_content.append($notfound);
    }
    $('<a href="#goodreads_dialog" id="modalTriggerLink"></a>').appendTo($("body"));
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

Goodreads.goodreads_dialog_template = `
<div id="goodreads_dialog" class="twbs modalDialog goodreads">
  <div class="twbs card document rounded">
    <div class="twbs card-body dialog rounded">
      <div class="twbs card-title">
        <h5 id="goodreads_title"></h5>
      </div>
      <div class="twbs card dialog_content" id="goodreads_content">
        <button id="close_btn" type="button" class="twbs dialog_close_btn">
          <span aria-hidden="true">&times;</span>
        </button>
        <div class="twbs card-group dialog_top_menu" id="goodreads_top_filter">
          <div class="twbs card text-center">
            <div class="twbs card-body">
              <div class="twbs row">
                <div class="twbs card col-12">
                  <div class="modal-menu">
                    <input type="checkbox" id="cb_show_goodreads_enable_sort" value="0">
                    <label class="checkbox_label" for="cb_show_goodreads_enable_sort">Sort By Date</label>
                    <input type="checkbox" id="cb_show_goodreads_sort_asc" value="1" checked>
                    <label class="checkbox_label" for="cb_show_goodreads_sort_asc">Newest to Oldest</label>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="poster_list" id="goodreads_poster_list">
        </div>
      </div>
    </div>
  </div>
</div>
`;

Goodreads.filterGoodreadsMedia = function(media)
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
    if ($("#cb_show_goodreads_enable_sort").prop("checked"))
    {
        selectedMedia = selectedMedia.sort(function(a, b){
            if ($("#cb_show_goodreads_sort_asc").prop("checked"))
                return -a.date+b.date;
            return a.date-b.date;
        });
    }
    return selectedMedia;
}

Goodreads.handle_goodreads = function(response, searchTerm)
{
    $goodreads_dialog = $(Goodreads.goodreads_dialog_template).appendTo($("body"));
    $goodreads_dialog = $("#goodreads_dialog");
    $goodreads_header = $("#goodreads_header");
    $goodreads_title  = $("#goodreads_title").text(`Results for "${searchTerm}"`);
    $goodreads_content = $("#goodreads_poster_list");
    $close_btn = $("#close_btn")
        .click(function(){
            $('#goodreads_dialog').remove();
        })
    $("[id^=cb_show_goodreads]").change(function(event){
            var selectedMedia = Goodreads.filterGoodreadsMedia(media);
            Goodreads.updatePosters(selectedMedia);
        });
    var media = response;
    // var selectedMedia = Goodreads.filterGoodreadsMedia(media);
    Goodreads.updatePosters(media);
}

Goodreads.addSearchQualifier = function(url, dict)
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

Goodreads.startHandlingGoodreadsAjax = function()
{
    dict ={};
    $goodreads_input = $("#goodreads_input");
    var searchTerm = encodeURI($goodreads_input.val());
    var url = `/app.php/snahp/api_proxy/goodreads/?s=${searchTerm}`;
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
        Goodreads.handle_goodreads(response, searchTerm);
    });
}

phpbb.addAjaxCallback('snahp.goodreadsCallback', Goodreads.startHandlingGoodreadsAjax);
$(document).ready(function() {
    $("#goodreads_input_author").keydown(function(event){
        if(event.keyCode == 13) {
            event.preventDefault();
            Goodreads.startHandlingGoodreadsAjax();
        }
    });
    $("#goodreads_input").keydown(function(event){
        if(event.keyCode == 13) {
            event.preventDefault();
            Goodreads.startHandlingGoodreadsAjax();
        }
    });
});
