<?php

namespace jeb\snahp\core\Rest\Fields;

class JsonField
{
    public function validate($data)
    {
        $data = htmlspecialchars_decode($data);
        json_decode($data);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidJSONStringError();
        }
        return $data;
    }

    public function serialize($validData)
    {
        return $validData;
    }
}

class InvalidJSONStringError extends \Exception
{
}
