<?php
namespace jeb\snahp\Apps\Wiki;

use \Symfony\Component\HttpFoundation\Response;
use \Symfony\Component\HttpFoundation\JsonResponse;

use \R as R;

class WikiController
{
    protected $db;/*{{{*/
    protected $user;
    protected $config;
    protected $request;
    protected $template;
    protected $container;
    protected $phpHelper;
    protected $tbl;
    protected $sauth;
    protected $helper;
    public function __construct(
        $db,
        $user,
        $config,
        $request,
        $template,
        $container,
        $phpHelper,
        $tbl,
        $sauth
    ) {
        $this->db = $db;
        $this->user = $user;
        $this->config = $config;
        $this->request = $request;
        $this->template = $template;
        $this->container = $container;
        $this->phpHelper = $phpHelper;
        $this->tbl = $tbl;
        $this->sauth = $sauth;
        $this->userId = (int) $this->user->data['user_id'];
        $this->sauth->reject_anon('Error Code: d77a3c5231');
    }/*}}}*/

    public function view()/*{{{*/
    {
        $cfg['tpl_name'] = '@jeb_snahp/wiki/rx_wiki.html';
        $cfg['title'] = 'Mass Mover V3';
        $this->table_prefix = 'phpbb_';
        $this->setupCustomCss();
        $this->embedGroup();
        return $this->phpHelper->render($cfg['tpl_name'], $cfg['title']);
    }/*}}}*/

    public function embedGroup()/*{{{*/
    {
        $groups = $this->sauth->get_user_groups($this->sauth->userId);
        $hidden_fields = [
            'userGroups' => implode(',', $groups),
        ];
        $s_hidden_fields = build_hidden_fields($hidden_fields);
        $this->template->assign_vars(
            [
            'USER_GROUP_MEMBERSHIP' => $s_hidden_fields,
            ]
        );
    }/*}}}*/

    public function testDiff()/*{{{*/
    {
        $new = '<<<HTML
          PHP is a server-side scripting language designed for web development but also used as a general-purpose programming language.
          As of January 2013, PHP was installed on more than 240 million websites (39% of those sampled) and 2.1 million web servers.
          Originally created by Rasmus Lerdorf in 1994, the reference implementation of PHP (powered by the Zend Engine) is now produced by The PHP Group.
        HTML';

        $old = '<<<HTML
              PHP is a server-side scripting language designed for web development but also used as a general-purpose programming language.
              PHP is now installed on more than 244 million websites and 2.1 million web servers.
              Originally created by Rasmus Lerdorf in 1995, the reference implementation of PHP is now produced by The PHP Group.
        HTML';

        $results = xdiff_string_diff($old, $new);
        $patch = xdiff_string_patch($old, $results);
        return new JsonResponse(['results'=>$results, 'patch'=> $patch]);
    }/*}}}*/

    public function setupCustomCss()/*{{{*/
    {
        $assets_version = $this->config['assets_version'];
        $user_style = $this->user->data['user_style'];
        $sql = 'SELECT style_name FROM ' . $this->table_prefix . 'styles
            WHERE style_id=' . $user_style;
        $result = $this->db->sql_query_limit($sql, 1);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        $style_name = $row['style_name'];
        $this->style_name = 'prosilver';
        switch ($style_name) {
        case 'Acieeed!':
            $this->template->assign_var('STYLE_NAME', 'acieeed');
            $this->template->assign_var('STYLE_TYPE', 'dark');
            $this->style_type = 'dark';
            $this->style_name = 'acieeed!';
            break;
        case 'Basic':
            $this->template->assign_var('STYLE_NAME', 'basic');
            $this->template->assign_var('STYLE_TYPE', 'light');
            $this->style_type = 'light';
            $this->style_name = 'basic';
            break;
        case 'Digi Orange':
            $this->template->assign_var('STYLE_NAME', 'digi_orange');
            $this->template->assign_var('STYLE_TYPE', 'dark');
            $this->style_type = 'dark';
            $this->style_name = 'digi_orange';
            break;
        case 'Hexagon':
            $this->template->assign_var('STYLE_NAME', 'hexagon');
            $this->template->assign_var('STYLE_TYPE', 'dark');
            $this->style_type = 'dark';
            $this->style_name = 'hexagon';
            break;
        case 'prosilver':
        default:
            $this->template->assign_var('STYLE_NAME', 'prosilver');
            $this->template->assign_var('STYLE_TYPE', 'light');
            $this->style_type = 'light';
            break;
        }
        $hidden_fields = [
            'snp_style_name' => $style_name,
            'snp_style_type' => $this->style_type,
        ];
        $s_hidden_fields = build_hidden_fields($hidden_fields);
        $this->template->assign_vars(
            [
            'S_STYLE_INFO' => $s_hidden_fields,
            'ASSETS_VERSION' => $assets_version,
            ]
        );
    }/*}}}*/
}
