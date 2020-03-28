<?php
namespace jeb\snahp\core\spotlight;

class helper
{
  protected $db;
  protected $user;
  protected $auth;
  protected $config;
  protected $config_text;
  protected $container;
  protected $tbl;
  protected $sauth;
  protected $this_user_id;
  public function __construct(
    $db, $user, $auth, $config, $config_text, $container,
    $tbl,
    $sauth
  )
  {/*{{{*/
    // settings
    $this->max_per_user = 6;
    $this->max_list = 6*12;
    $this->job_queue = [
      [
        'f_data' => function($row){
          if (!$this->is_imdb($row)) return;
          $json = $this->extract_imdb($row['post_text'], true);
          $title = $json['title'];
          $poster_url = $json['poster'];
          if (!$poster_url || !$title) return;
          return [
            'title' => $title,
            'poster_url' => $poster_url,
            'topic_id' => $row['topic_id'],
            'poster' => $row['topic_first_poster_name'],
            'colour' => $row['topic_first_poster_colour'],
          ];
        },
      ],
    ];

    $this->db = $db;
    $this->user = $user;
    $this->auth = $auth;
    $this->config = $config;
    $this->config_text = $config_text;
    $this->container = $container;
    $this->t = $tbl;
    $this->sauth = $sauth;
    $this->user_id = $this->user->data['user_id'];
    $this->users = [];
  }/*}}}*/

  public function select_candidates()/*{{{*/
  {
    $forum_ids = explode(',', $this->config['snp_pg_fid_listing']);
    $where = $this->db->sql_in_set('topics.forum_id', $forum_ids);
    $where .= ' AND topics.topic_first_post_id=posts.post_id';
    $order_by = 'topics.topic_time DESC';
    $sql_array = [
      'SELECT'	=> '
      topics.topic_id, posts.post_text, topics.topic_poster,
      topics.topic_first_poster_name, topics.topic_first_poster_colour
      ',
      'FROM'		=> [TOPICS_TABLE => 'topics'],
      'LEFT_JOIN'	=> [
        [
          'FROM'	=> [POSTS_TABLE => 'posts'],
          'ON'	=> 'topics.topic_id=posts.topic_id',
        ],
      ],
      'WHERE'		=> $where,
      'ORDER_BY' => $order_by,
    ];
    $sql = $this->db->sql_build_query('SELECT', $sql_array);
    $result = $this->db->sql_query_limit($sql, 2000, 0, 0);
    $rowset = $this->db->sql_fetchrowset($result);
    $this->db->sql_freeresult($result);
    return $rowset;
  }/*}}}*/

  private function is_imdb($row)/*{{{*/
  {
    preg_match('#\<s>\[imdb]</s>(.*?)<e>\[/imdb]</e>#s', $row['post_text'], $match);
    return !!$match;
  }/*}}}*/

  private function extract_imdb($message)/*{{{*/
  {
    preg_match('#\<s>\[imdb]</s>(.*?)<e>\[/imdb]</e>#s', $message, $match);
    return json_decode($match[1], true);
  }/*}}}*/

  public function clear_cache()/*{{{*/
  {
    $this->config_text->set('snp_spotlight_cache', json_encode([]));
  }/*}}}*/

  public function update_list()/*{{{*/
  {
      $rowset = $this->select_candidates();
      $rowset = $this->filter($rowset);
      $this->set_cache_list($rowset);
  }/*}}}*/

  public function set_cache_list($data)/*{{{*/
  {
    $this->config_text->set('snp_spotlight_cache', json_encode($data));
  }/*}}}*/

  public function get_cache_list()/*{{{*/
  {
    return $this->config_text->get('snp_spotlight_cache');
  }/*}}}*/

  public function filter($rowset)/*{{{*/
  {
    $res = [];
    foreach ($rowset as $row)
    {
      $topic_poster = $row['topic_poster'];
      if (!isset($this->users[$topic_poster]))
      {
        $this->users[$topic_poster] = 1;
      }
      if ($this->users[$topic_poster] > $this->max_per_user) continue;
      foreach( $this->job_queue as $props )
      {
        if ($tmp = $props['f_data']($row))
        {
          $res[] = $tmp;
          if (count($res) >= $this->max_list) return $res;
          $this->users[$topic_poster] += 1;
          continue;
        }
      }
    }
    return $res;
  }/*}}}*/

}
