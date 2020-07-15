<?php

namespace jeb\snahp\core\Rest\Fields;

class GenericField extends Field
{
    public function validate($data)
    {
        return (string) $data;
    }
}
