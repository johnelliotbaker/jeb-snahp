<?php

namespace jeb\snahp\core\Rest\Fields;

class BoolField extends Field
{
    public function validate($data)
    {
        return (bool) $data;
    }
}
