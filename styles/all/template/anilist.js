function makeAnilistTemplate(data)
{
    console.log(data);
    try { var img                                          = `[center][img width="300"]${data['coverImage']['large']}[/img][/center]\n`;
    } catch(err) { var img                                 = ""; }
    try { var year                                         = `[center][size=150][color=#000000][b](${data['startDate']['year']})[/b][/color][/size][/center]\n`;
    } catch(err) { var year                                = ""; }
    try { if (data['title']['romaji']) { var titleRomaji   = `[center][size=150][color=#FF0000][b]${data['title']['romaji']}[/b][/color][/size][/center]\n`;}
    } catch(err) { var titleRomaji                         = ""; }
    try { if (data['title']['english']) { var titleEnglish = `[center][size=150][color=#FF0000][b]${data['title']['english']}[/b][/color][/size][/center]\n`;}
    } catch(err) {var titleEnglish                         = ""; }
    try { if (data['title']['native']) { var titleNative   = `[center][size=150][color=#FF0000][b]${data['title']['native']}[/b][/color][/size][/center]\n`;}
    } catch(err) { var titleNative                         = ""; }
    try { var director                                     = `[color=#FF8000][b]Director[/b][/color]: ${data['Director']}\n`
    } catch(err) { var director                            = ""; }
    try { var actors                                       = `[color=#FF8000][b]Stars[/b][/color]: ${data['Actors']}\n`
    } catch(err) { var actors                              = ""; }
    try { var runtime                                      = `[color=#FF8000][b]Runtime[/b][/color]: ${data['duration']} minutes\n`
    } catch(err) { var runtime                             = ""; }
    try { var genre                                        = `[color=#FF8000][b]Genre[/b][/color]: ${data['genres'].join(', ')}\n`
    } catch(err) { var genre                               = ""; }
    try { var rating                                       = `[color=#FF8000][b]Rating[/b][/color]: ${data['averageScore']}\n`
    } catch(err) { var rating                              = ""; }
    try { var votes                                        = `[color=#FF8000][b]Votes[/b][/color]: ${numberWithCommas(data['popularity'])}\n`
    } catch(err) { var votes                               = ""; }
    try { var reldate                                      = `[color=#FF8000][b]Release Date[/b][/color]: ${data['startDate']['year']}\n`
    } catch(err) { var reldate                             = ""; }
    try { var vrating                                      = `[color=#FF8000][b]Viewer Rating (TV/MPAA)[/b][/color]: ${data['Rated']}\n`
    } catch(err) { var vrating                             = ""; }
    try { var summary                                      = `[color=#FF8000][b]Summary[/b][/color]: [i]${data['description']}[/i]\n`
    } catch(err) { var summary                             = ""; }
    try { var links                                        = `[color=#FF8000][b]Links[/b][/color]: [b]`
    } catch(err) { var links                               = ""; }
    try { var dataurl                                      = `[url=https://www.data.com/title/${data['dataID']}]data[/url]\n`
    } catch(err) { var dataurl                             = ""; }
    try { var ddl                                          = `[color=#0000FF][b]Direct Download Links[/color][/b]: \n`
    } catch(err) { var ddl                                 = ""; }
    try { var dlink                                        = `[hide][b][url=https://links.snahp.it/xxxx][color=#FF0000]MEGA[/color][/url]
[url=https://links.snahp.it/xxxx][color=#FF0000]ZippyShare[/color][/url]
[url=https://snahp.it/?s=tt1270797][color=#FF0000]ZippyShare[/color][/url]
[/b][/hide]\n`
    } catch(err) { var dlink    = ""; }
    console.log(titleEnglish);
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
    <div id="anilist_dialog" class="modal">
    <div class="modal-overlay modal-toggle"></div>
    <div class="modal-wrapper modal-transition">
    <div id="anilist_header" class="modal-header">
    <h2 id = ""class="modal-heading">
    <button type="button" id="close_btn">Close</button>
    </h2>
    </div>

    <div class="modal-body">
    <div id="anilist_content" class="modal-content">
    </div>
    </div>
    </div>
    </div>
`;

function handle(response)
{
    $anilist_dialog = $(anilist_dialog_template).appendTo($("body"));
    $anilist_header = $("#anilist_header");
    $anilist_content = $("#anilist_content");
    $close_btn = $("#close_btn")
        .click(function(){
            $('.modal').remove();
        })

    var responseType = getResponseType(response);
    var media = response['data']['Page']['media'];
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
                $('.modal').remove();
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
    $('.modal').toggleClass('is-visible');
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
        handle(response);
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
