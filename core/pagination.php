<?php
namespace jeb\snahp\core;

class pagination
{

	public function __construct(
	)
	{
	}

    public function makejs($base_url='/', $total=0, $per_page=0, $start=0)
    {
        $arr = [1,2,3,4,5,6,7];
        $html = '<nav aria-label="Page navigation"><ul class="pagination">';
        $html .= '<li class="page-item"><a class="page-link" href="#">Previous</a></li>';
        foreach ($arr as $key => $entry)
        {
            if ($key==4)
            {
                $active = ' active';
            }
            else
            {
                $active = '';
            }
            $html .= "<li onclick='test({$key});' class='page-item{$active}'><a class='page-link'>{$key}</a></li>";
        }
        $html .= '<li class="page-item"><a class="page-link" href="#">Next</a></li>';
        $html .= '</ul></nav>';
        return $html;
    }

    public function make($base_url='/', $total=0, $per_page=0, $start=0)
    {
        $html = '<nav aria-label="Page navigation"><ul class="pagination">';
        $n = ceil($total / $per_page);
        $i = ceil($start / $per_page)+1;
        $prev = ($i <= 1) ? 0 : $start - $per_page;
        $next = ($i > $n-1) ? ($n-1)*$per_page : $i*$per_page;
        $prev = $base_url . "&per_page={$per_page}&start={$prev}";
        $next = $base_url . "&per_page={$per_page}&start={$next}";
        $html .= "<li class='page-item'><a class='page-link' href='{$prev}'>Previous</a></li>";
        $html .= "<li class='page-item'><a class='page-link' href='{$next}'>Next</a></li>";
        $html .= '</ul></nav>';
        return $html;
    }

}
