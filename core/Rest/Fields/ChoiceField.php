<?php

namespace jeb\snahp\core\Rest\Fields;

class ChoiceField extends Field
{
    public function validate($choice)
    {
        if (!array_key_exists("choices", $this->_options)) {
            throw new \Exception("ChoiceField requires choices.");
        }
        $choices = $this->_options["choices"];
        if (!in_array($choice, $choices)) {
            throw new \Exception(
                "${choice} is not one of: " . implode(", ", $choices)
            );
        }
        return (int) $data;
    }
}
