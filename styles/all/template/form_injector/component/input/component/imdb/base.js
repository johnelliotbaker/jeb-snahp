// KEYS KEYS AND MORE KEYS

var Imdb = {};

aKey = [
    // "b0dea1ac", // Deactivated
    "bb25f904",
    "b10ca448",
    "bb3702c0",
    "a22f1f7f",
    "54e3324c"
]

Imdb.getRating = function(aRating)
{
    strn = "";
    for (var i in aRating)
    {
        var rating = aRating[i];
        var source = rating['Source'];
        var value = rating['Value'];
        switch (source)
        {
            case "Internet Movie Database":
                strn = strn + `IMDb: ${value} `;
                break;
            case "Rotten Tomatoes":
                strn = strn + `RT: ${value} `;
                break;
            case "Metacritic":
                strn = strn + `MC: ${value} `;
                break;
        }
    }
    return strn;
}

Imdb.maketemplate = function(imdb)
{
    try { var img               = `[center][img width="300"]${imdb['Poster']}[/img][/center]\n`;
    } catch(err) { var img      = ""; }
    try { var title             = `[center][size=150][color=#FF0000][b]${imdb['Title']} (${imdb['Year']})[/b][/color][/size][/center]\n`;
    } catch(err) { var title    = ""; }
    try { var year              = `(${imdb['Year']})`;
    } catch(err) { var year     = ""; }
    try { var director          = `[color=#FF8000][b]Director[/b][/color]: ${imdb['Director']}\n`
    } catch(err) { var director = ""; }
    try { var actors            = `[color=#FF8000][b]Stars[/b][/color]: ${imdb['Actors']}\n`
    } catch(err) { var actors   = ""; }
    try { var runtime           = `[color=#FF8000][b]Runtime[/b][/color]: ${imdb['Runtime']} (taken from IMDb)\n`
    } catch(err) { var runtime  = ""; }
    try { var genre             = `[color=#FF8000][b]Genre[/b][/color]: ${imdb['Genre']}\n`
    } catch(err) { var genre    = ""; }
    try { 
        var rr = /(.*)\/(.*)/.exec(imdb['Ratings'][0]['Value']);
        // rr = Imdb.getRating(imdb['Ratings']);
        var rating            = `[color=#FF8000][b]Rating[/b][/color]: ${rr[0]}* (may differ)\n`
    } catch(err) { var rating   = ""; }
    try { var votes             = `[color=#FF8000][b]Votes[/b][/color]: ${imdb['imdbVotes']} (may differ)\n`
    } catch(err) { var votes    = ""; }
    try { var reldate           = `[color=#FF8000][b]Release Date[/b][/color]: ${imdb['Released']} (taken from IMDb)\n`
    } catch(err) { var reldate  = ""; }
    try { var vrating           = `[color=#FF8000][b]Viewer Rating (TV/MPAA)[/b][/color]: ${imdb['Rated']} (taken from IMDb)\n`
    } catch(err) { var vrating  = ""; }
    try { var summary           = `[color=#FF8000][b]Summary[/b][/color]: [i]${imdb['Plot']}[/i]\n`
    } catch(err) { var summary  = ""; }
    try { var links             = `[color=#FF8000][b]Links[/b][/color]: `
    } catch(err) { var links    = ""; }
    try { var imdburl           = `[url=https://www.imdb.com/title/${imdb['imdbID']}]IMDb ${imdb['imdbID']}[/url]\n`
    } catch(err) { var imdburl  = ""; }
    try { var ddl               = `[color=#0000FF][b]Direct Download Links[/b][/color]: \n`
    } catch(err) { var ddl      = ""; }
    try { var dlink             = `[hide][b][url=https://links.snahp.it/xxxx][color=#FF0000]MEGA[/color][/url]
[url=https://links.snahp.it/xxxx][color=#FF0000]ZippyShare[/color][/url]
[url=https://snahp.it/?s=${imdb['imdbID']}][color=#FF0000]ZippyShare[/color][/url]
[/b][/hide]\n`
    } catch(err) { var dlink    = ""; }
    var text = img + '\n' + title + '\n' + 
        director + actors + '\n' +
        runtime + genre + '\n' +
        rating + votes +'\n' +
        reldate + vrating + '\n' +
        summary + '\n' +
        links + '[b]' +  imdburl + '[/b]' + '\n' +
        ddl + dlink;
    return text;
}

Imdb.getKey = function()
{
    var max = aKey.length;
    var min = 0;
    var index = Math.floor(Math.random() * (max - min) + min);
    return aKey[index];
}

Imdb.fillPostMessage = function(term)
{
    var url = `https://www.omdbapi.com/?apikey=${Imdb.getKey()}&plot=full&i=${term}`
    $ajax = $.ajax({url: url, dataType:'jsonp'});
    $ajax.done(function(response){
        var summary = Imdb.maketemplate(response);
        var text = summary;
        $('#message').val(text);
    });
}


Imdb.isImdbID = function(strn)
{
    var regex = /ev\d{7,8}\/\d{4}(-\d)?|(ch|co|ev|nm|tt)\d{7,8}/;
    var match = strn.match(regex);
    return match
}


Imdb.getResponseType = function(response)
{
    if ('Search' in response)
    {
        return 'search';
    }
    else if ('Title' in response)
    {
        return 'title';
    }
    return false;
}


Imdb.startHandlingImdbAjax = function()
{
    $imdb_input = $("#imdb_input");
    var term = $imdb_input.val();
    var match = Imdb.isImdbID(term)
    if (match && match[0])
    {
        var url = `https://www.omdbapi.com/?apikey=${Imdb.getKey()}&i=${match[0]}`
    }
    else
    {
        var url = `https://www.omdbapi.com/?apikey=${Imdb.getKey()}&s=` + space2dash(term);
    }
    $ajax = $.ajax({url: url, dataType:'jsonp'});
    $ajax.done(function(response){
        Imdb.handle_imdb(response);
    });
}

var imdb_dialog_template = `
    <div id="imdb_dialog" class="modal_imdb">
    <div class="modal-overlay modal-toggle"></div>
    <div class="modal-wrapper modal-transition">
    <div id="imdb_header" class="modal-header">
    <h2 id = ""class="modal-heading">
    <button type="button" id="close_btn">Close</button>
    </h2>
    </div>
    <div class="modal-body">
    <div id="imdb_content" class="modal-content">
    </div>
    </div>
    </div>
    </div>
`

Imdb.getImdbChunkiness = function(entry)
{
    var aCat = ['Poster', 'Title', 'Year', 'imdbID', 'Type',];
    var chunkiness = 0;
    for (var cat of aCat)
    {
        if (entry[cat] && entry[cat] != "N/A")
        {
            chunkiness += 1;
        }
    }
    return chunkiness;
}

Imdb.handle_imdb = function(response)
{
    $imdb_dialog = $(imdb_dialog_template).appendTo($("body"));
    $imdb_header = $("#imdb_header");
    $imdb_content = $("#imdb_content");
    $close_btn = $("#close_btn")
        .click(function(){
            $('#imdb_dialog').remove();
        })
    var responseType = Imdb.getResponseType(response);
    var aEntry = response['Search'] || [response];
    var count = 0;
    var aExclusion = ["N/A"];
    for (var entry of aEntry)
    {
        var minChunkiness = 3;
        var posterUrl = entry['Poster'];
        if (Imdb.getImdbChunkiness(entry) > minChunkiness ||
            posterUrl && !aExclusion.includes(posterUrl)
            )
        {
            $li = $("<li/>")
                .addClass("img_li")
                .appendTo($imdb_content);
            $imgDiv = $("<div/>")
                .addClass("img_container")
                .appendTo($li);
            $img = $("<img/>")
                .attr({
                    "id": "img-" + count,
                    "src": entry["Poster"],
                    "imdbID": entry["imdbID"],
                    })
                .width("150")
                .height("225")
                .click( function(e) {
                    target = e.target;
                    var imdbid = $(target).attr("imdbid");
                    Imdb.fillPostMessage(imdbid);
                    $('#imdb_dialog').remove();
                })
                .appendTo($imgDiv)
            $type_txt = $("<div/>")
            .addClass("bottom-right")
                .html(`
                ${entry["Title"]}<br>
                ${entry["Year"]}<br>
                ${entry["Type"]}<br>
                <a href="https://www.imdb.com/title/${entry["imdbID"]}">IMDb</a>
            `)
            .appendTo($imgDiv);
            count++;
        }
    }
    if (count == 0)
    {
        $notfound = $("<h>Not Found</h>").css({"font-size": "200%"});
        $imdb_content.append($notfound);
    }
    $('#imdb_dialog').toggleClass('is-visible');
}

$(document).ready(function() {
    $("#imdb_input").keydown(function(event){
        if(event.keyCode == 13) {
            event.preventDefault();
            Imdb.startHandlingImdbAjax();
        }
    });
});
