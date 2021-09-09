<?php
namespace jeb\snahp\Apps\MysqlSearch;

use \Symfony\Component\HttpFoundation\Response;
use \Symfony\Component\HttpFoundation\JsonResponse;

class MysqlSearchController
{
    const MAX_PER_PAGE = 100;
    const DEFAULT_PER_PAGE = 50;
    protected $request;
    protected $template;
    protected $phpHelper;
    protected $sauth;
    protected $helper;
    protected $formHelper;
    public function __construct(
        $request,
        $template,
        $phpHelper,
        $sauth,
        $helper,
        $formHelper
    ) {
        $this->request = $request;
        $this->template = $template;
        $this->phpHelper = $phpHelper;
        $this->sauth = $sauth;
        $this->helper = $helper;
        $this->formHelper = $formHelper;
        $this->userId = (int) $this->sauth->userId;
    }

    public function respond()
    {
        $methodName = $this->request->server('REQUEST_METHOD', 'GET');
        if ($methodName !== 'GET') {
            return new JsonResponse([], 404);
        }
        $cfg['tpl_name'] = '@jeb_snahp/mysql_search/base.html';
        $cfg['title'] = 'Elite Search';
        return $this->handleMysqlSearch($cfg);
    }

    private function handleMysqlSearch($cfg)
    {

        $required = [ 'forum_type' => 'listings', 'word_to_search' => '' ];
        $rv = $this->formHelper->getRequestVars($required);
        $this->formHelper->setTemplateVars($rv);
        $forumType = $rv['forum_type'];
        if ($tplName = $cfg['tpl_name']) {
            $wordToSearch = $this->request->variable('word_to_search', '');
            if ($wordToSearch === '') {
                return $this->phpHelper->render($tplName, $cfg['title']);
            } elseif (strlen($wordToSearch) < 2) {
                throw new QueryTooShortError('Expected query to be at least 2 letters. Error Code: 824935a492');
            }
            add_form_key('jeb_snp');
            // IF SUBMITTED
            $data = [];
            $total = 0;
            $pg = new \jeb\snahp\core\pagination();
            $perPage = min(
                $this->request->variable('per_page', $this::DEFAULT_PER_PAGE),
                $this::MAX_PER_PAGE
            );
            $start = $this->request->variable('start', 0);
            $baseUrl = $this->phpHelper->route(
                'jeb_snahp_routing.mysql_search.main',
                [ 'word_to_search' => $wordToSearch, 'forum_type' => $forumType, ]
            );
            [$data, $total] = $this->helper->search($wordToSearch, $perPage, $start, $forumType);
            $pagination = $pg->make($baseUrl, $total, $perPage, $start);
            $this->template->assign_vars(
                [ 'PAGINATION' => $pagination, ]
            );
            foreach (array_values($data) as $i => $row) {
                $group = [
                    'TOPIC_TITLE' => $row['topic_title'],
                    'TOPIC_ID'    => $row['topic_id'],
                    'POST_ID'     => isset($row['post_id']) ? "#p{$row['post_id']}" : '',
                    'ID'          => $start + $i + 1,
                ];
                $this->template->assign_block_vars('postrow', $group);
            }
            return $this->phpHelper->render($tplName, $cfg['title']);
        }
    }
}

class QueryTooShortError extends \Exception
{
}
