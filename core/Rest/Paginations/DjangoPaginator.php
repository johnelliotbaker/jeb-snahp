<?php

namespace jeb\snahp\core\Rest\Paginations;

use jeb\snahp\core\Rest\Paginations\Page;

class DjangoPaginator
{
    public function __construct($objectList, $perPage, $orphans=0, $allowEmptyFirstPage=true)
    {
        $this->objectList = $objectList;
        $this->_checkObjectListIsOrdered();
        $this->perPage = $perPage;
        $this->orphans = $orphans;
        $this->allowEmptyFirstPage = $allowEmptyFirstPage;
    }

    public function validateNumber($number)
    {
        try {
            $number = (int)$number;
        } catch (Exception $e) {
            throw new PageNotAnInteger("That page number is not an integer. Error Code: 77bbea4e67");
        }
        if ($number < 1) {
            throw new EmptyPage("That page number is less than 1. Error Code: d3d1499052");
        }
        if ($number > $this->numPages()) {
            if ($number == 1 && $this->allowEmptyFirstPage) {
            } else {
                throw new EmptyPage("That page contains no results. Error Code: 279bcc5efc");
            }
        }
        return $number;
    }

    public function getPage($number)
    {
        try {
            $number = $this->validateNumber($number);
        } catch (PageNotAnInteger $e) {
            $number = 1;
        } catch (EmptyPage $e) {
            $number = $this->numPages();
        }
        return $this->page($number);
    }

    public function page($number)
    {
        $number = $this->validateNumber($number);
        $bottom = ($number - 1) * $this->perPage;
        return $this->_getPage(array_slice($this->objectList, $bottom, $this->perPage), $number, $this);
    }

    private function _getPage(...$args)
    {
        return new Page(...$args);
    }

    public function count()
    {
        return count($this->objectList);
    }

    public function numPages()
    {
        $hits = max(1, $this->count() - $this->orphans);
        return ceil($hits / $this->perPage);
    }

    private function _checkObjectListIsOrdered()
    {
        $ordered =  isset($this->objectList['ordered']) ? $this->objectList['ordered'] : null;
        if ($ordered !== null && !$ordered) {
            // Show some warnings
        }
    }
}


class InvalidPage extends \Exception
{
};

class PageNotAnInteger extends \Exception
{
};

class EmptyPage extends \Exception
{
};
