<?php
namespace jeb\snahp\Apps\Xmas;

class BingoBoard /*{{{*/
{
    public function __construct($rows, $columns, $poolSize)
    {
        $this->rows = $rows;
        $this->columns = $columns;
        $this->poolSize = $poolSize;
        $this->len = $rows * $columns;
        $this->pool = range(1, $this->poolSize);
    }

    public function print()
    {
        print_r("<br/>");
        foreach ($this->tiles as $row) {
            $strn = implode(' ', $row);
            print_r($strn);
            print_r("<br/>");
        }
        print_r("<br/>");
    }


    public function make($numbers)
    {
        [$rows, $columns] = [$this->rows, $this->columns];
        $tiles = [];
        for ($row = 0; $row < $rows; $row++) {
            $tiles[] = array_slice($numbers, $row * $columns, $columns);
        }
        $this->tiles = $tiles;
        return $this;
    }

    public function makeRandom()
    {
        $pool = (array) $this->pool;
        shuffle($pool);
        return $this->make($pool);
    }

    public function flip($r, $c)
    {
        $this->tiles[$r][$c] = -1 * $this->tiles[$r][$c];
    }

    public function markSequence($sequence)
    {
        $sequence = array_flip($sequence);
        foreach ($this->tiles as $r => $row) {
            foreach ($row as $c => $col) {
                if (isset($sequence[$col])) {
                    $this->flip($r, $c);
                }
            }
        }
    }
}/*}}}*/
