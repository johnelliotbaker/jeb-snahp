<?php
namespace jeb\snahp\Apps\RequestForm\Models;

class Base
{
    const TYPE = 'BASE';

    public $data;
    public $contentFields = [];

    public function __get($name)/*{{{*/
    {
    }/*}}}*/

    public function __set($name, $value)/*{{{*/
    {
        $this->{$name} = $value;
    }/*}}}*/

    public function __construct()/*{{{*/
    {
        $this->data = [
            'type' => strtolower($this::TYPE),
            'title' => 'No Title',
            'content' => [],
        ];
    }/*}}}*/

    public function makeBBCode()/*{{{*/
    {
        if ($this->canMakeBBCode()) {
            return '[request]'
                . json_encode($this->makeData(), JSON_PRETTY_PRINT)
                . '[/request]';
        }
    }/*}}}*/

    public function canMakeBBCode()/*{{{*/
    {
        return $this::TYPE !== 'NULL';
    }/*}}}*/

    public function makeData()/*{{{*/
    {
        foreach ($this->contentFields as $field) {
            $value = $this->{$field};
            if ($value !== null) {
                $this->data['content'][$field] = $this->{$field};
            }
        }
        return $this->data;
    }/*}}}*/
}
