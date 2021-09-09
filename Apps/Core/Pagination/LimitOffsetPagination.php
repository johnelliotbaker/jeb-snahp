<?php

namespace jeb\snahp\Apps\Core\Pagination;

use Symfony\Component\HttpFoundation\JsonResponse;

// require_once '/var/www/forum/ext/jeb/snahp/Apps/Core/Pagination/DjangoPaginator.php';

use jeb\snahp\core\Rest\Paginations\InvalidPage;

const DJANGO_PAGINATOR = "jeb\snahp\Apps\Core\Pagination\DjangoPaginator";
const PAGE_SIZE = 20;

class LimitOffsetPagination
{
    public $defaultLimit = PAGE_SIZE;
    public $limitQueryParam = "limit";
    public $limitQueryDescription = "Number of results to return per page.";
    public $offsetQueryParam = "offset";
    public $offsetQueryDescription = "The initial index from which to return the results.";
    public $maxLimit = 100;
    public $template = "rest_framework/pagination/numbers.html";

    public function paginateQueryset($queryset, $request, $view = null)
    {
        $this->limit = $this->getLimit($request);
        if ($this->limit === null) {
            return null;
        }
        $this->count = $this->getCount($queryset);
        $this->offset = $this->getOffset($request);
        $this->request = $request;
        if ($this->count > $this->limit && $this->template !== null) {
            $this->displayPageControls = true;
        }
        if ($this->count === 0 || $this->offset > $this->count) {
            return [];
        }
        return $queryset->slice($this->offset, $this->limit);
    }

    public function getCount($queryset)
    {
        return count($queryset);
    }

    public function getPaginatedResult($data)
    {
        return [
            "count" => $this->count,
            "offset" => $this->offset,
            "results" => $data,
        ];
    }

    public function getLimit($request)
    {
        if ($this->limitQueryParam) {
            try {
                return _positiveint(
                    $request->variable($this->limitQueryParam, ""),
                    $strict = true,
                    $cutoff = $this->maxLimit
                );
            } catch (KeyError | ValueError $e) {
            }
        }
        return $this->defaultLimit;
    }

    public function getOffset($request)
    {
        try {
            return _positiveInt(
                $request->variable($this->offsetQueryParam, "")
            );
        } catch (KeyError | ValueError $e) {
            return 0;
        }
    }
}

function _positiveInt($integerString, $strict = false, $cutoff = null)
{
    $ret = (int) $integerString;
    if ($ret < 0 || ($ret === 0 && $strict)) {
        throw new ValueError();
    }
    if ($cutoff) {
        return min($ret, $cutoff);
    }
    return $ret;
}

class KeyError extends \Exception
{
}

class ValueError extends \Exception
{
}
