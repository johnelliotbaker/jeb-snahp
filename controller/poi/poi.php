<?php
namespace jeb\snahp\controller\poi;
use \Symfony\Component\HttpFoundation\Response;
use \Symfony\Component\HttpFoundation\JsonResponse;

class poi
{
  protected $db;
  protected $user;
  protected $config;
  protected $request;
  protected $template;
  protected $container;
  protected $helper;
  protected $tbl;
  protected $sauth;
  // protected $logger;
  public function __construct(
    $db, $user, $config, $request, $template, $container, $helper,
    $tbl,
    $sauth
  )/*{{{*/
  {
    $this->db = $db;
    $this->user = $user;
    $this->config = $config;
    $this->request = $request;
    $this->template = $template;
    $this->container = $container;
    $this->helper = $helper;
    $this->tbl = $tbl;
    $this->sauth = $sauth;
    $this->user_id = (int) $this->user->data['user_id'];
    $this->sauth->reject_anon('Error Code: 5954856517');
  }/*}}}*/

  public function handle($mode)/*{{{*/
  {
    switch ($mode)
    {
    case 'poi':
      $cfg['tpl_name'] = '@jeb_snahp/poi/base.html';
      $cfg['title'] = 'Points of Interest';
      return $this->respond_poi($cfg);
    default:
      break;
    }
    trigger_error('Nothing to see here. Move along.');
  }/*}}}*/

  private function get_user_data($user_ids)/*{{{*/
  {
    if (is_array($user_ids))
    {
      $where = $this->db->sql_in_set('user_id', $user_ids);
      $sql = 'SELECT user_id, username, user_colour FROM ' . USERS_TABLE . " WHERE ${where}";
      $result = $this->db->sql_query($sql);
      $rowset = [];
      while ($row = $this->db->sql_fetchrow($result))
      {
        $rowset[$row['user_id']] = $row;
      }
      $this->db->sql_freeresult($result);
      return $rowset;
    }
    else
    {
      $where = "user_id=${user_ids}";
      $sql = 'SELECT user_id, username, user_colour FROM ' . USERS_TABLE . " WHERE ${where}";
      $result = $this->db->sql_query($sql);
      $row = $this->db->sql_fetchrow($result);
      $this->db->sql_freeresult($result);
      return $row;
    }
  }/*}}}*/

  private function get_tags()/*{{{*/
  {
    $sql = 'SELECT tag, tag_lowercase, count FROM ' . $this->tbl['topic_tags'] . ' order by count DESC';
    $result = $this->db->sql_query($sql, 600);
    $rowset = $this->db->sql_fetchrowset($result);
    $this->db->sql_freeresult($result);
    return $rowset;
  }/*}}}*/

  public function respond_poi($cfg)/*{{{*/
  {
    $excluded_items = ['encoder_group', 'crouching_tiger', 'hidden_dragon'];
    $users_raw = $this->container->getParameter('jeb.snahp.avatar.badge.users');
    $items_raw = $this->container->getParameter('jeb.snahp.avatar.badge.items');
    $items = [];
    foreach($items_raw as $k => $v)
    {
      if (in_array($k, $excluded_items)) continue;
      $items[$k] = [
        'type' =>  $v['description'],
        'img_url' => isset($v['data']['img_url']) ? $v['data']['img_url'] : '',
        'users' => [],
      ];
    }
    $user_ids =  array_keys($users_raw);
    $user_db_data = $this->get_user_data($user_ids);
    foreach($users_raw as $user_id => $user_data)
    {
      $queue = $user_data['queue'];
      $data = $user_data['data'];
      if (isset($user_db_data[$user_id]))
      {
        foreach ($queue as $group_name)
        {
          if (!in_array($group_name, $excluded_items))
          {
            $user_group_data = $data[$group_name];
            $url = isset($user_group_data['url']) ? $user_group_data['url'] : '';
            $user = [
              'username' => $user_db_data[$user_id]['username'],
              'user_colour' => $user_db_data[$user_id]['user_colour'],
              'url' => $url,
            ];
            switch ($group_name) {
              case 'encoder':
                $tagname = $user_group_data['tagname'];
                $user['url'] = "/search.php?keywords=${tagname}&terms=all&author_id=${user_id}&sc=1&sf=titleonly&sr=topics&sk=x&sd=d&st=0&ch=300&t=0&submit=Search";
                break;
              default:
                break;
            }
            $items[$group_name]['users'][] = $user;
          }
        }
      }
    }
    $this->template->assign_var('ITEMS', $items);



    $tags = $this->get_tags();
    $this->template->assign_var('TAGS', $tags );

    return $this->helper->render($cfg['tpl_name'], $cfg['title']);
  }/*}}}*/

}
