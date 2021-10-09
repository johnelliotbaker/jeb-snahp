<?php

namespace jeb\snahp\core\Rest\Fields;

class StringField extends Field
{
    public function validate($data)
    {
        if (!is_string($data)) {
            throw new \Exception("Expected a string, got ${data}.");
        }
        $maxLength = $this->_options["max_length"]
            ? (int) $this->_options["max_length"]
            : null;
        if ($maxLength !== null) {
            $count = strlen($data);
            if ($count > $maxLength) {
                $message = "Expected max length of ${maxLength}. Got ${count}.";
                throw new \Exception($message);
            }
        }
        return (string) $data;
    }
}
