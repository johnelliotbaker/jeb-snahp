<?php

namespace jeb\snahp\core\Rest\Fields;

class StringField extends Field
{
    public function validate($data)
    {
        if (!is_string($data)) {
            throw new \Exception("Expected a string, got ${data}.");
        }
        return (string) $data;
    }
}
