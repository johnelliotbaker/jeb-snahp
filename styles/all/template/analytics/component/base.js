function json_common_thanks()
{
    var a_tid = $('#tid_searchbox').val();
    a_tid = a_tid.trim().replace(/\s+/g, ',');
    a_tid = encodeURI(a_tid);
    var url = '/app.php/snahp/analytics/handle/json_common_thanks/?t=' + a_tid;
    $.get(url)
        .done((resp)=>{
            $('#content').empty();
            for (var entry of resp)
            {
                var html = `<tr>
  <th class="id text-center">${entry['id']}</th>
  <th class="userid text-center">${entry['user_id']}</th>
  <td class="username text-center">
      <b><span style="font-size: 1.1em">${entry['username_link']}</span></b>
      <a target="_blank" href="/app.php/snahp/thanks/handle/thanks_given/?user_id=${entry['user_id']}">
          <i class="icon fa-thumbs-up fa-fw" aria-hidden="true"></i><span class="sr-only">Show thanks given</span>
      </a>
  </td>
  <td class="count text-center">${entry['n_thanks']}</td>
  <td class="topics">${entry['topic_links']}</td>
</tr>`;
                $elem = $(html).appendTo($('#content'));
            }
        });
}
window.onload = ()=>{
    json_common_thanks();
};
