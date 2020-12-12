<?php
namespace jeb\snahp\Apps\Xmas;

class ScoreRule75 /*{{{*/
{
    public $sequence = [];
    public function scoreRow($rowData)
    {
        foreach ($rowData as $value) {
            if (!in_array($value, $this->sequence)) {
                return 0;
            }
        }
        return 1;
    }

    public function scoreRows($tiles)
    {
        $score = 0;
        foreach ($tiles as $row) {
            $score += $this->scoreRow($row);
        }
        return $score;
    }

    public function scoreDiagonals($tiles, $rows, $columns)
    {
        [$toRight, $toLeft] = [1, 1];
        foreach (range(0, $rows-1) as $r) {
            if (!in_array($tiles[$r][$r], $this->sequence)) {
                $toRight = 0;
                break;
            }
        }
        foreach (range(0, $rows-1) as $r) {
            if (!in_array($tiles[$r][$columns - $r - 1], $this->sequence)) {
                $toLeft = 0;
                break;
            }
        }
        return $toRight + $toLeft;
    }

    public function transpose($matrix)
    {
        $res = [];
        foreach ($matrix as $key => $subarr) {
            foreach ($subarr as $subkey => $subvalue) {
                $res[$subkey][$key] = $subvalue;
            }
        }
        return $res;
    }

    public function score($board)
    {
        $tiles = is_string($board->tiles) ? json_decode($board->tiles) : $board->tiles;
        [$rows, $columns] = [$board->rows, $board->columns];
        $score = 0;
        $score += $this->scoreRows($tiles);
        $score += $this->scoreRows($this->transpose($tiles));
        $score += $this->scoreDiagonals($tiles, $rows, $columns);
        return $score;
    }
}/*}}}*/



class ScoreRule75_1 /*{{{*/
{
    public function scoreRow($rowData)
    {
        $total = 0;
        foreach ($rowData as $value) {
            if ($value < 0) {
                $total += 1;
            }
        }
        return count($rowData) == $total ? 1 : 0;
    }

    public function scoreRows($tiles)
    {
        $score = 0;
        foreach ($tiles as $row) {
            $score += $this->scoreRow($row);
        }
        return $score;
    }

    public function scoreDiagonals($tiles, $rows, $columns)
    {
        [$toRight, $toLeft] = [1, 1];
        foreach (range(0, $rows-1) as $r) {
            if ($tiles[$r][$r] >= 0) {
                $toRight = 0;
                break;
            }
        }
        foreach (range(0, $rows-1) as $r) {
            if ($tiles[$r][$columns - $r - 1] >= 0) {
                $toLeft = 0;
                break;
            }
        }
        return $toRight + $toLeft;
    }

    public function transpose($matrix)
    {
        $res = [];
        foreach ($matrix as $key => $subarr) {
            foreach ($subarr as $subkey => $subvalue) {
                $res[$subkey][$key] = $subvalue;
            }
        }
        return $res;
    }

    public function score($board)
    {
        $tiles = is_string($board->tiles) ? json_decode($board->tiles) : $board->tiles;
        [$rows, $columns] = [$board->rows, $board->columns];
        $score = 0;
        $score += $this->scoreRows($tiles);
        $score += $this->scoreRows($this->transpose($tiles));
        $score += $this->scoreDiagonals($tiles, $rows, $columns);
        return $score;
    }
}/*}}}*/
