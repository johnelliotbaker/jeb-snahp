<?php

namespace jeb\snahp\core\Rest\Fields;

class Field
{
    public function __construct($options = [])
    {
        $this->default = array_key_exists('default', $options) ? $options['default'] : null;
        $this->_options = $options;
    }

    public function validate($data)
    {
        return $data;
    }

    public function serialize($validData)
    {
        return $validData;
    }
}
