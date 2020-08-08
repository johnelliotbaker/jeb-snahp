<?php
namespace jeb\snahp\Apps\Wiki;

require_once 'ext/jeb/snahp/core/Rest/RedBeanSetup.php';

use \Symfony\Component\HttpFoundation\Response;
use \Symfony\Component\HttpFoundation\JsonResponse;
use jeb\snahp\core\Rest\RedBeanSetup;

class ArticleView
{
    use RedBeanSetup;
    /*{{{*/
    protected $request;
    protected $sauth;
    public function __construct($request, $sauth)
    {
        $this->request = $request;
        $this->sauth = $sauth;
        // $this->connectDatabase(false);
        // $this->sauth->reject_anon('Error Code: a5e8ee80c7');
    }/*}}}*/

    public function view($groupName, $articleName)/*{{{*/
    {
        $entry = \R::findOne('phpbb_snahp_wiki_entry', 'subject=?', [$articleName]);
        return new JsonResponse($entry);
    }/*}}}*/

    public function makeGroup($group)
    {
        $fields = ['id', 'subject'];
        $name = $group->name;
        $id = $group->id;
        $entries = \R::find('phpbb_snahp_wiki_entry', 'phpbb_snahp_wiki_group_id=?', [$id]);
        $entries = array_map(
            function ($arg) use ($name) {
                $data['id'] = $arg->id;
                $data['subject'] = $arg->subject;
                $data['group'] = $name;
                return $data;
            },
            $entries
        );
        return array_values($entries);
    }
}
