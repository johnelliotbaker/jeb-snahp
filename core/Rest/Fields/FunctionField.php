<?php

namespace jeb\snahp\core\Rest\Fields;

class FunctionField extends Field
{
    public $f;

    public function __construct($f)
    {
        $this->f = $f;
    }

    public function validate($data)
    {
        $f = $this->f;
        return (string) $f();
    }
}
