$(document).ready(()=>{
    $('body').append('<span id="nothing"></span>');
    $oneday = $("#my_open_requests");
    $oneday.click((e)=>{
        $('#nothing').click();
        var url = '/app.php/snahp/reqs/myrequests/';
        $.get(url, (resp)=>{
            $table = $("#open_request_table");
            $table.empty();
            $dialog = $("#open_request_dialog");
            $dialog.dialog();
            $dialog.dialog("option", "minWidth", 600);
            $('.ui-button').css('outline', 'none');
            for (var entry of resp)
            {
                var title = entry.ti;
                if (title){
                    var url = '<td><b><a href="/viewtopic.php?t=' + entry.t + '">' + title + '</a></b></td>';
                    var datetime = '<td>' + entry.d + '</td>';
                    var status = '<td>' + entry.s + '</td>';
                    var row = '<tr>' + url + datetime + status + '</tr>';
                    $table.append($(row))
                }
            }
        })
    });
});
