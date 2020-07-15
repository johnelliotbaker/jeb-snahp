<?php

namespace jeb\snahp\core\Rest\Fields;

class StringField extends Field
{
    public function validate($data)
    {
        return (string) $data;
    }
}
