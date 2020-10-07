<?php

namespace jeb\snahp\core\Rest\Paginations;

require_once '/var/www/forum/ext/jeb/snahp/core/Rest/Paginations/DjangoPaginator.php';
require_once '/var/www/forum/ext/jeb/snahp/core/Rest/Paginations/Page.php';

use jeb\snahp\core\Rest\Paginations\InvalidPage;

const DJANGO_PAGINATOR = 'jeb\snahp\core\Rest\Paginations\DjangoPaginator';
const PAGE_SIZE = 10;

class PageNumberPagination
{
    public $pageSize = PAGE_SIZE;
    public $djangoPaginatorClass = DJANGO_PAGINATOR;
    public $pageQueryParam = 'page';
    public $pageQueryDescription = "Number of results to return per page.";
    public $maxPageSize = null;
    public $lastPageStrings = ['last'];
    public $template = '';
    public $invalidPageMessage = 'Invalid Page. Error Code: 814b1ceeaf';

    public function paginateQueryset($queryset, $request, $view=null)
    {
        $pageSize = $this->getPageSize($request);
        if (!$pageSize) {
            return null;
        }

        $paginator = $this->djangoPaginatorClass($queryset, $pageSize);
        $pageNumber = $request->variabl($this->pageQueryParam, 1);
        if (in_array($pageNumber, $this->lastPageStrings)) {
            $pageNumber = $paginator->numPages();
        }

        try {
            $this->page = $paginator->page($pageNumber);
        } catch (InvalidPage $e) {
            trigger_error("Not Found. Error Code: 9c0f5e03d4");
        }

        if ($paginator->numPages() > 1 && $this->template !== null) {
            $this->displayPageControls = true;
        }
        $this->request = $request;
        // TODO: This is why we need to turn Page into iterator
        // return list($this->page);
        return $this->page;
    }
}
