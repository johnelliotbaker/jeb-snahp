var cmtx = {}


cmtx.generate_user_thanks = function(user_id) {
    var user_id = $row[0].dataset.userid
    var a_tid = $row.find('.topics').text()
    var url = '/app.php/snahp/analytics/handle/json_user_thanks/?t=' + a_tid + '&u=' + user_id;
    var f = {};
    f.user_id = user_id;
    $.get(url)
        .done((resp)=>{
            $(`.cmtx_details_${f['user_id']}`).remove();
            setTimeout(function() {
                var html = `<tr class="cmtx_details_${f['user_id']}"><td colspan="5"><table class="tbl" style="width:100%; border:3px solid #222; color: #000;">
                    <thead class="thead-dark"><tr>
                    <th>Topic id</th>
                    <th>Title</th>
                    <th>Thanked Time</th>
                    </tr>
                    </thead>
                    `;
                for (entry of resp)
                {
                    html += `
                    <tr style="background-color:#d8d8d8; font-weight:900;">
                    <td><a href="/viewtopic.php?t=${entry['topic_id']}" target="_blank">${entry['topic_id']}</td>
                    <td><a href="/viewtopic.php?t=${entry['topic_id']}" target="_blank">${entry['topic_title']}</td>
                    <td>${entry['thanks_time']}</td>
                    </tr>
                    `
                }
                html += '</table></td></tr>';
                $row.after(html);
            }.bind(this), 100);
        })
}

cmtx.show_user_list = function() {
    var a_tid = $('#tid_searchbox').val();
    a_tid = a_tid.trim().replace(/\s+/g, ',');
    a_tid = encodeURI(a_tid);
    var url = '/app.php/snahp/analytics/handle/json_common_thanks/?t=' + a_tid;
    $.get(url)
        .done((resp)=>{
            $('#content').empty();
            for (var entry of resp)
            {
                var html = `<tr id="cmtx_user_${entry['user_id']}" data-userid="${entry['user_id']}">
  <th class="id text-center">${entry['id']}</th>
  <th class="userid text-center">${entry['user_id']}</th>
  <td class="username text-center">
      <b><span style="font-size: 1.1em">${entry['username_link']}</span></b>
      <a target="_blank" href="/app.php/snahp/thanks/handle/thanks_given/?user_id=${entry['user_id']}">
          <b>(${entry['n_total_thanks']})</b>
      </a>
  </td>
  <td class="count text-center">${entry['n_thanks']}</td>
  <td class="topics">${entry['topic_links']}</td>
</tr>`;
                $row = $(html);
                $row.click((event) => {
                    $row = $(event.target).parent();
                    cmtx.generate_user_thanks($row);
                })
                $elem = $row.appendTo($('#content'));
            }
        });
}

window.onload = ()=>{
};
