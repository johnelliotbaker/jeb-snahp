<?php

namespace jeb\snahp\core\Rest\Fields;

class Field
{
    public function validate($data)
    {
        return $data;
    }

    public function serialize($validData)
    {
        return $validData;
    }
}
