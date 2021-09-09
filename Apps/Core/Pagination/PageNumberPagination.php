<?php

namespace jeb\snahp\Apps\Core\Pagination;

use Symfony\Component\HttpFoundation\JsonResponse;

// require_once '/var/www/forum/ext/jeb/snahp/Apps/Core/Pagination/DjangoPaginator.php';

use jeb\snahp\core\Rest\Paginations\InvalidPage;

const DJANGO_PAGINATOR = "jeb\snahp\Apps\Core\Pagination\DjangoPaginator";
const PAGE_SIZE = 20;

class PageNumberPagination extends BasePageNumberPagination
{
    public function paginateQueryset($queryset, $request, $view = null)
    {
        $pageSize = $this->getPageSize($request);
        if (!$pageSize) {
            return null;
        }
        $paginator = new $this->djangoPaginatorClass($queryset, $pageSize);
        $pageNumber = $request->variable($this->pageQueryParam, 1);
        $this->page = $paginator->page($pageNumber);
        $offset = $this->page->startIndex();
        $this->page->objectList;
        return $this->page->objectList;
    }

    public function getPaginatedResult($data)
    {
        return [
            "count" => $this->page->paginator->count(),
            "total_pages" => $this->page->paginator->numPages(),
            "current" => $this->page->number,
            "results" => $data,
        ];
    }
}

class BasePageNumberPagination
{
    public $pageSize = PAGE_SIZE;
    public $djangoPaginatorClass = DJANGO_PAGINATOR;
    public $pageQueryParam = "page";
    public $pageQueryDescription = "A page number within the paginated result set.";
    public $pageSizeQueryParam = "page-size";
    public $pageSizeQueryDescription = "Number of results to return per page.";
    public $maxPageSize = null;
    public $lastPageStrings = ["last"];
    public $template = "";
    public $invalidPageMessage = "Invalid Page. Error Code: 814b1ceeaf";

    public function paginateQueryset($queryset, $request, $view = null)
    {
        $pageSize = $this->getPageSize($request);
        if (!$pageSize) {
            return null;
        }
        $paginator = new $this->djangoPaginatorClass($queryset, $pageSize);
        $pageNumber = $request->variable($this->pageQueryParam, 1);
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

    public function getPageSize($request)
    {
        if ($this->pageSizeQueryParam) {
            try {
                return max(
                    1,
                    $request->variable($this->pageSizeQueryParam, PAGE_SIZE)
                );
            } catch (Exception $e) {
            }
        }
        return $this->pageSize;
    }

    public function getNextLink()
    {
        if (!$this->page->hasNext()) {
            return;
        }
        try {
            $pageNumber = $this->page->nextPageNumber();
        } catch (EmptyPage $e) {
            return;
        }
        $data = ["page" => $pageNumber];
        return buildAbsoluteUri($this->request, $data);
    }

    public function getPreviousLink()
    {
        if (!$this->page->hasPrevious()) {
            return;
        }
        try {
            $pageNumber = $this->page->previousPageNumber();
        } catch (EmptyPage $e) {
            return;
        }
        $data = ["page" => $pageNumber];
        return buildAbsoluteUri($this->request, $data);
    }

    public function getPaginatedResponse($data)
    {
        $data = [
            "count" => $this->page->paginator->count(),
            "next" => $this->getNextLink(),
            "previous" => $this->getPreviousLink(),
            "results" => $data,
        ];
        return new JsonResponse($data, 200);
    }
}
