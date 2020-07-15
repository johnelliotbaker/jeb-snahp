<?php

namespace jeb\snahp\core\Rest\Fields;

class IntegerField extends Field
{
    public function validate($data)
    {
        if (!is_numeric($data)) {
            trigger_error("Expected an integer.");
        }
        return (int) $data;
    }
}
