<?php

namespace jeb\snahp\core\Rest\Fields;

class DateField extends Field
{
    public function validate($data)
    {
        if (!is_numeric($data)) {
            trigger_error("Expected an integer.");
        }
        return (int) $data;
    }
}
