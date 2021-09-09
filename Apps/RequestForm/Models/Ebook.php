<?php
namespace jeb\snahp\Apps\RequestForm\Models;

class Ebook extends Base
{
    const TYPE = "EBOOK";
    public $contentFields = [
        "filehost",
        "authors",
        "language",
        "format",
        "isbn",
        "edition",
        "link",
    ];
}
