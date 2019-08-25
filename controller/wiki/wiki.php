<?php
namespace jeb\snahp\controller\wiki;
use \Symfony\Component\HttpFoundation\Response;
use \Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Forum Wiki
 * */

class wiki
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
    protected $parsedown;
    protected $basedir;
    public function __construct(
    $db, $user, $config, $request, $template, $container, $helper,
    $tbl,
    $sauth, $parsedown
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
        $this->parsedown = $parsedown;
        $this->wikidir = 'ext/jeb/snahp/styles/all/template/wiki/';
        $this->datadir = $this->wikidir . 'data/';
        $this->templatedir = '@jeb_snahp/wiki/';
    }/*}}}*/

    public function handle($mode)/*{{{*/
    {
        $cfg['tpl_name'] = $this->templatedir . 'base.html';
        $cfg['name'] = $mode;
        return $this->render($cfg);
    }/*}}}*/

    private function render($cfg)/*{{{*/
    {
        $name = $cfg['name'] . '.md';
        $path = $this->datadir . $name;
        if (!file_exists($path))
        {
            trigger_error('That wiki entry does not exist. Error Code: 976eaa8ce1');
        }
        $f = fopen($path, 'r') or die('unable');
        $text = fread($f, filesize($path));
        $html = $this->parsedown->text($text);
        $this->template->assign_var('HTML', $html);
        return $this->helper->render($cfg['tpl_name'], 'Snahp Economy Dashboard');
    }/*}}}*/

}
