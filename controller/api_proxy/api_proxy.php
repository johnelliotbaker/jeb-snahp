<?php
namespace jeb\snahp\controller\api_proxy;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

class api_proxy
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
    protected $key;
    public function __construct(
        $db,
        $user,
        $config,
        $request,
        $template,
        $container,
        $helper,
        $tbl,
        $sauth
    ) {
        $this->db = $db;
        $this->user = $user;
        $this->config = $config;
        $this->request = $request;
        $this->template = $template;
        $this->container = $container;
        $this->helper = $helper;
        $this->tbl = $tbl;
        $this->sauth = $sauth;
        $this->user_id = $this->user->data["user_id"];
        $this->key = "KdAxWqOkdsboMf1Kc8A";
    }

    public function handle($mode)
    {
        if ($this->sauth->user_belongs_to_groupset($this->user_id, "Basic")) {
            trigger_error(
                "You do not have permission to view this page. Error Code: 80d6d3faf5"
            );
        }
        switch ($mode) {
            case "goodreads":
                return $this->respond_goodreads_as_json();
        }
        trigger_error("Invalide mode. Error Code: 1c53aa401a");
    }

    // private function respond_goodreads_from_book_id_with_json($book_id)
    private function respond_goodreads_as_json()
    {
        $cmd = $this->request->variable("cmd", "search");
        $s = $this->request->variable("s", "");
        switch ($cmd) {
            case "search":
                return $this->respond_goodreads_search_as_json();
            case "book":
                return $this->respond_goodreads_from_book_id_as_json();
            default:
                break;
        }
        trigger_error("error");
        // $tpl = '@jeb_snahp/test/base.html';
        // $this->template->assign_vars([
        //     'ROWSET' => $res,
        // ]);
        // return $this->helper->render($tpl, 'sup');
    }

    private function respond_goodreads_from_book_id_as_json()
    {
        $book_id = $this->request->variable("bid", 0);
        $arr = [];
        $url = "https://www.goodreads.com/book/show.xml?key=KdAxWqOkdsboMf1Kc8A&id=${book_id}";
        $body = file_get_contents($url);
        $res = json_decode(
            json_encode(
                (array) simplexml_load_string(
                    $body,
                    "SimpleXMLElement",
                    LIBXML_NOCDATA
                )
            ),
            1
        ); // I know, I'm a terrible human being
        $res = $res["book"];
        return new JsonResponse($res);
    }

    private function respond_goodreads_search_as_json()
    {
        $s = urlencode($this->request->variable("s", ""));
        $url = "https://www.goodreads.com/search/index.xml?key=KdAxWqOkdsboMf1Kc8A&q=${s}";
        $arr = [];
        $body = file_get_contents($url);
        $contents = json_decode(
            json_encode(
                (array) simplexml_load_string(
                    $body,
                    "SimpleXMLElement",
                    LIBXML_NOCDATA
                )
            ),
            1
        ); // I know, I'm a terrible human being
        $res = $contents["search"]["results"]["work"];
        return new JsonResponse($res);
    }
}
