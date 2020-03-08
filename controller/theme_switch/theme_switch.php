<?php
namespace jeb\snahp\controller\theme_switch;
use \Symfony\Component\HttpFoundation\Response;
use \Symfony\Component\HttpFoundation\JsonResponse;

class theme_switch
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
    case 'theme_switch':
      return $this->respond_theme_switch_as_json();
    default:
      break;
    }
    trigger_error('Nothing to see here. Move along.');
  }/*}}}*/


  private function set_user_style($style_name)
  {
    $style_name_ref = [
      'prosilver' => 'prosilver',
      'basic' => 'Basic',
      'acieeed!' => 'Acieeed!',
      'hexagon' => 'Hexagon',
    ];
    $style_name = $this->db->sql_escape($style_name);
    prn($style_name);
    $style_name =  isset($style_name_ref[$style_name]) ? $style_name_ref[$style_name] : 'prosilver';
    prn($style_name);
    $user_id = (int) $this->user->data['user_id'];
    $sql = 'SELECT style_id FROM ' . STYLES_TABLE . " WHERE style_name='${style_name}'";
    $result = $this->db->sql_query($sql);
    $row = $this->db->sql_fetchrow($result);
    $this->db->sql_freeresult($result);
    if ($row)
    {
      $style_id = (int) $row['style_id'];
      $sql = 'UPDATE ' . USERS_TABLE . " SET user_style=${style_id} WHERE user_id=${user_id}"; 
      $this->db->sql_query($sql);
      return true;
    }
    return false;
  }

  public function respond_theme_switch_as_json()/*{{{*/
  {
    $style_name = (string) $this->request->variable('style_name', 'prosilver');
    $success = $this->set_user_style($style_name);
    switch ($success) {
    case true:
      return new JsonResponse(['status' => 'success'], 200);
    }
    return new JsonResponse(['status' => 'failure'], 404);
  }/*}}}*/

}
