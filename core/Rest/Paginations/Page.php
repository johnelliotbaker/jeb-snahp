<?php

namespace jeb\snahp\core\Rest\Paginations;

class Page
{
    public function __construct($objectList, $number, $paginator)
    {
        $this->objectList = $objectList;
        $this->number = $number;
        $this->paginator = $paginator;
    }

    public function length()
    {
        return count($this->objectList);
    }

    public function hasNext()
    {
        return $this->number < $this->paginator->numPages();
    }

    public function hasPrevious()
    {
        return $this->paginator->numPages() > 1;
    }

    public function hasOtherPages()
    {
        return $this->hasPrevious() || $this->hasNext();
    }

    public function nextPageNumber()
    {
        return $this->paginator->validateNumber($this->number + 1);
    }

    public function previousPageNumber()
    {
        return $this->paginator->validateNumber($this->number - 1);
    }

    public function startIndex()
    {
        if ($this->paginator->count() === 0) {
            return 0;
        }
        return $this->paginator->perPage * ($this->number - 1) + 1;
    }

    public function endIndex()
    {
        if ($this->number == $this->paginator->numPages()) {
            return $this->paginator->count();
        }
        return $this->number * $this->paginator->perPage;
    }
}
