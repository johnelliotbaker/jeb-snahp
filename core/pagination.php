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
        $html .= '<li class="page-item noselect"><a class="page-link" href="#">Previous</a></li>';
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
            $html .= "<li onclick='test({$key});' class='page-item noselect{$active}'><a class='page-link'>{$key}</a></li>";
        }
        $html .= '<li class="page-item noselect"><a class="page-link" href="#">Next</a></li>';
        $html .= '</ul></nav>';
        return $html;
    }

    private function get_start($total, $per_page, $i_block, $base_url)
    {
        $i_start = 0;
        $block_start = 1;
        $prepend_char = (substr($base_url, -1)==='/') ? '?' : '&';
        $url = $base_url . "${prepend_char}per_page={$per_page}&start={$i_start}";
        $data = [
            'block' => 1,
            'index' => $i_start,
        ];
        if ($i_block <= $block_start)
        {
            $html = '<li class="page-item noselect"><span class="page-link" style="cursor: default;">&nbsp;</span></li>';
        }
        else
        {
            $html = '<li class="page-item noselect"><a class="page-link" href="' . $url . '">' . $block_start . '</a></li>';
        }
        return $html;
    }

    private function get_end($total, $per_page, $i_block, $base_url)
    {
        $block_end = ceil($total / $per_page);
        $i_end = (int) ($block_end - 1) * $per_page;
        $prepend_char = (substr($base_url, -1)==='/') ? '?' : '&';
        $url = $base_url . "${prepend_char}per_page={$per_page}&start={$i_end}";
        $data = [
            'block' => $block_end,
            'index' => $i_end,
        ];
        if ($i_block >= $block_end)
        {
            $html = '<li class="page-item noselect"><span class="page-link" style="cursor: default;">&nbsp;</span></li>';
        }
        else
        {
            $html = '<li class="page-item noselect"><a class="page-link" href="' . $url . '">' . $block_end . '</a></li>';
        }
        return $html;
    }

    private function get_prev($total, $per_page, $i_block, $n_block, $base_url, $start)
    {
        $block_start = 1;
        $prev = ($i_block <= 1) ? 0 : $start - $per_page;
        $prepend_char = (substr($base_url, -1)==='/') ? '?' : '&';
        $url = $base_url . "${prepend_char}per_page={$per_page}&start={$prev}";
        if ($i_block <= $block_start)
        {
            $html = '<li class="page-item noselect"><span class="page-link" style="cursor: default;">Prev</span></li>';
        }
        else
        {
            $html = '<li class="page-item noselect"><a class="page-link" href="' . $url . '">Prev</a></li>';
        }
        return $html;
    }

    private function get_next($total, $per_page, $i_block, $n_block, $base_url)
    {
        $block_end = ceil($total / $per_page);
        $next = ($i_block > $n_block-1) ? ($n_block-1)*$per_page : $i_block*$per_page;
        $prepend_char = (substr($base_url, -1)==='/') ? '?' : '&';
        $url = $base_url . "{$prepend_char}per_page={$per_page}&start={$next}";
        if ($i_block >= $block_end)
        {
            $html = '<li class="page-item noselect"><span class="page-link" style="cursor: default;">Next</span></li>';
        }
        else
        {
            $html = '<li class="page-item noselect"><a class="page-link" href="' . $url . '">Next</a></li>';
        }
        return $html;
    }

    private function get_baseurl($a_exclude=[])/*{{{*/
    {
        global $request;
        $request->enable_super_globals();
        $query = $_SERVER['QUERY_STRING'];
        $self = $_SERVER['PHP_SELF'];
        $request->disable_super_globals();
        parse_str($query, $query);
        $query = array_filter($query, function($key) use($a_exclude) {return !in_array($key, $a_exclude);}, ARRAY_FILTER_USE_KEY);
        $query = http_build_query($query);
        return $self . '?' . $query;
    }/*}}}*/

    public function make($base_url=null, $total=0, $per_page=0, $start=0)
    {
        if ($base_url===null)
        {
            $base_url = $this->get_baseurl(['per_page', 'start']);
        }
        $html = '<nav aria-label="Page navigation"><ul class="pagination">';
        $n_block = ceil($total / $per_page);
        $i_block = ceil($start / $per_page)+1;
        $html .= $this->get_prev($total, $per_page, $i_block, $n_block, $base_url, $start);
        $html .= $this->get_start($total, $per_page, $i_block, $base_url);
        $html .= "<li class='page-item noselect'><span class='page-link' style='background-color: #007bff; color:white;'>{$i_block}</span></li>";
        $html .= $this->get_end($total, $per_page, $i_block, $base_url);
        $html .= $this->get_next($total, $per_page, $i_block, $n_block, $base_url);
        $html .= '</ul></nav>';
        return $html;
    }

    public function make_fixed($base_url='/', $total=0, $per_page=0, $start=0)
    {
        if ($total < $per_page)
        {
            return '';
        }
        $html = '<nav aria-label="Page navigation"><ul class="pagination">';
        $n = ceil($total / $per_page);
        $i = ceil($start / $per_page)+1;
        $prev = ($i <= 1) ? 0 : $start - $per_page;
        $next = ($i > $n-1) ? ($n-1)*$per_page : $i*$per_page;
        $prev = $base_url . "?per_page={$per_page}&start={$prev}";
        $next = $base_url . "?per_page={$per_page}&start={$next}";
        $html .= "<li class='page-item noselect'><a class='page-link' href='{$prev}'>Previous</a></li>";
        $html .= "<li class='page-item noselect'><a class='page-link' href='{$next}'>Next</a></li>";
        $html .= '</ul></nav>';
        return $html;
    }

}
