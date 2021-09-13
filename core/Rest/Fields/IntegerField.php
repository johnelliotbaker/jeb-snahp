<?php

namespace jeb\snahp\core\Rest\Fields;

class IntegerField extends Field
{
    public function validate($data)
    {
        if (!is_numeric($data)) {
            $name = $this->getName();
            $name = "($name)";
            throw new \Exception("$name Expected an integer.");
        }
        return (int) $data;
    }
}
