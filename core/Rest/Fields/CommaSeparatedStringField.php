<?php

namespace jeb\snahp\core\Rest\Fields;

class CommaSeparatedStringField extends Field
{
    public function validate($data)
    {
        $data = preg_replace("#[\s,]+#", ",", $data);
        $data = preg_replace('#,$#', "", $data);
        if ($data === "") {
            return "";
        }
        $data = explode(",", $data);
        return $this->serialize($data);
    }

    public function serialize($validData)
    {
        return implode(",", $validData);
    }
}
