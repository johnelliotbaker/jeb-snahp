<?php

namespace jeb\snahp\core\Rest\Fields;

class CommaSeparatedPositiveIntegerField extends Field
{
    public function validate($data)
    {
        $data = preg_replace('#[\s,]+#', ',', $data);
        $data = preg_replace('#,$#', '', $data);
        if ($data === '') {
            return '';
        }
        $data = explode(',', $data);
        $arr = array_map('intval', $data);
        foreach ($arr as $val) {
            if ($val < 1) {
                trigger_error("Comma separated integer must be greater than 0.");
            }
        }
        $data = array_values(array_unique($arr));
        return $this->serialize($data);
    }

    public function serialize($validData)
    {
        return implode(',', $validData);
    }
}
