<?php
namespace jeb\snahp\Apps\Bookmark;

use \Symfony\Component\HttpFoundation\Response;
use \Symfony\Component\HttpFoundation\JsonResponse;

class BookmarkController
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
        $sauth,
        $helper
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
        $this->helper = $helper;
        $this->userId = $sauth->userId;
        $this->sauth->reject_anon('Error Code: a5e8ee80c7');
    }/*}}}*/

    public function removeMyBookmark($id)/*{{{*/
    {
        $this->helper->removeUserBookmark($id);
        return new JsonResponse([]);
    }
    public function mybookmark($type)/*{{{*/
    {
        $allowedTypes = ['viewtopic', 'basic'];
        if (!in_array($type, $allowedTypes)) {
            throw new \Exception('Now allowed. Error Code: 1e3d775ad1');
        }
        $userId = $this->userId;
        switch ($method = $this->request->server('REQUEST_METHOD')) {
        case 'GET':
            $userId = $this->userId;
            $results = $this->helper->getUserBookmarks($userId, $type);
            $resp = new JsonResponse($results);
            $resp->setEncodingOptions(JSON_NUMERIC_CHECK);
            return $resp;
        case 'POST':
            $url = htmlspecialchars_decode($this->request->variable('url', ''));
            $params = htmlspecialchars_decode($this->request->variable('params', ''));
            $hash = htmlspecialchars_decode($this->request->variable('hash', ''));
            $name = htmlspecialchars_decode($this->request->variable('name', ''));
            try {
                $results = $this->helper->saveUserBookmark($userId, $type, $url, $params, $hash, $name);
            } catch (\Exception $e) {
                return new JsonResponse(['reason' => $e->getMessage()], 400);
            }
            $resp = new JsonResponse($results);
            $resp->setEncodingOptions(JSON_NUMERIC_CHECK);
            return $resp;
        default:
            print_r('NOTHING');
            break;
        }
    }/*}}}*/
}
