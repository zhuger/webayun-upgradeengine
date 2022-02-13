<?php
namespace webayun\upgradeengine\Core\Page;

class BasePage{
    public $firstRow;
    public $listRows;
    public $parameter;
    public $totalRows;
    public $totalPages;
    public $rollPage   = 10;
    public $lastSuffix = true;

    private $p       = 'page';
    private $url     = '';
    private $nowPage = 1;


	private $needForm;
	private $pageFormID = "";
	private $pageFunctionName = 'pagination_page';
	private $pageSizeID = "pagesize";
	private $pageID = "page";


    private $config = array(
        'header' => '<td class="left"><div class="page-size-box">共<span>%TOTAL_ROW%</span>条，每页显示%PageSize% 分%TOTAL_PAGE%页</div></td><td class="center"></td>',
        'prev'   => '上一页',
        'next'   => '下一页',
        'first'  => '首页',
        'last'   => '...%TOTAL_PAGE%',
        'theme'=>'%HEADER%<td class="right" align="right">
                <div class=pagebreak>
                <table cellspacing="0" cellpadding="0">
                <tbody>                
                <td class="page_text page_home">%FIRST%</td>
				<td class="page_text page_prev">%UP_PAGE%</td>
                <td class="page_no">%LINK_PAGE%</td>
                <td class="page_text page_next">%DOWN_PAGE%</td>
                <td class="page_text page_end">%END%</td>
                <td class="page_jump">
                <form action="" method="get">
                前往第
                <span><input type="text" name="page" value="%PAGE%" class="gopage">
                </span>页<input type="submit" value="GO" class="go">
                </form>
                </td>
                </tbody>
                </table>
                </div>',
		'theme2'=>'%HEADER%<td class="right" align="right">
                <div class=pagebreak>
                <table cellspacing="0" cellpadding="0">
                <tbody>                
                <td class="page_text page_home">%FIRST%</td>
				<td class="page_text page_prev">%UP_PAGE%</td>
                <td class="page_no">%LINK_PAGE%</td>
                <td class="page_text page_next">%DOWN_PAGE%</td>
                <td class="page_text page_end">%END%</td>
                <td class="page_jump">
                前往第
                <span><input type="text" id="_pagination_page" value="%PAGE%" class="gopage" />
                </span>页<input type="button" value="GO" class="go" %GO_FUNC% />
                </td>
                </tbody>
                </table>
                </div>'
    );

    public function __construct($url = '', $totalRows, $listRows = 20, $parameter = array(),$needForm=true)
    {

        $this->url = $url;
        $this->totalRows = $totalRows;
        $this->listRows  = $listRows;
        $this->parameter = empty($parameter) ? $_GET : $parameter;
		foreach( $this->parameter as $key=>$item ){
			unset($this->parameter[$key]);
			$this->parameter[htmlspecialchars($key,ENT_QUOTES)] = $item;
		}
        $this->nowPage   = empty($_GET[$this->p]) ? 1 : intval($_GET[$this->p]);
        $this->nowPage   = $this->nowPage > 0 ? $this->nowPage : 1;
		$this->totalPages = ceil($this->totalRows / $this->listRows);
        if($this->nowPage>$this->totalPages&&$this->totalPages>0){
            $this->nowPage=$this->totalPages;
        }
        if($this->nowPage<=0){
            $this->nowPage=1;
        }
        $this->firstRow  = $this->listRows * ($this->nowPage - 1);
		$this->needForm = $needForm;
    }

	public function setPageFunctionName($name) {
		$this->pageFunctionName = $name;
	}

	public function setFormID($id) {
		$this->pageFormID = $id;
	}

    private function url($page)
    {
        return str_replace(urlencode('[PAGE]'), $page, $this->url);
    }


    private function pageFuncStr($page) {
    	return "javascript:" . $this->pageFunctionName . "(".$page.")";
	}

	public function pagination() {
		return $this->show();
	}

	private function javascript() {
		$submitForm = <<<EOL
<script type="text/javascript">
function pagination_submit() {
	console.log($("#{$this->pageFormID}"));
	console.log("{$this->url}");
	//return false;
	$("#{$this->pageFormID}").submit();
}
function pagination_pagesize(size) {
	$('#{$this->pageSizeID}').val(size);
	$('#{$this->pageID}').val(1);
	pagination_submit();
}
function pagination_page(page) {
	$('#{$this->pageID}').val(page);
	pagination_submit();
}
</script>
EOL;
		return $submitForm;
	}

	public function pagesizeSelect() {
		$pagesizes = array(10,20,30,50,100);
		$s='<select class="select" onchange="pagination_pagesize(this.value)">';
		for($i=0; $i<count($pagesizes); $i++) {
			$size = $pagesizes[$i];
			if ($this->listRows == $size) {
				$s.='<option value="'.$size.'" selected>'.$size.'条</option>';
			} else {
				$s.='<option value="'.$size.'">'.$size.'条</option>';
			}
		}

		return $s.'</select>';
	}

	public function hiddenParametersHtml($params=null) {
        if (is_null($params) || !is_array($params) || empty($params)){
            $params = array();
        }

		$params[] = array('page', $this->nowPage, 'page');
		$params[] = array('pagesize', $this->listRows, 'pagesize');

		$s = "";
		foreach ($params as $v) {
			if ($v[2]) {
				$idattr = "id='".$v[2]."'";
			} else {
				$idattr = "";
			}

			$s.="<input type='hidden' name='".$v[0]."' value='".$v[1]."' $idattr />" . PHP_EOL;
		}

		return $s;
	}

    public function show()
    {
        if (0 == $this->totalRows) {
        	if (!$this->needForm) {
				return $this->javascript();
			} else {
                return $this->javascript();
			}
        }

        $this->parameter[$this->p] = '[PAGE]';
		$params = '';
		foreach ($this->parameter as $k => $v) {
			$params .= "&$k=$v";
		}
        $this->url                 = $this->url . $params;
        $this->totalPages = ceil($this->totalRows / $this->listRows);
        if (!empty($this->totalPages) && $this->nowPage > $this->totalPages) {
            $this->nowPage = $this->totalPages;
        }

        $now_cool_page      = $this->rollPage / 2;
        $now_cool_page_ceil = ceil($now_cool_page);
        $this->lastSuffix && ($this->config['last'] = $this->totalPages);

		if (!$this->needForm) {
			$gofunc = 'onclick="javascript:'.$this->pageFunctionName.'(document.getElementById(\'_pagination_page\').value)"';

			$up_row  = $this->nowPage - 1;
			$up_page = $up_row > 0 ? '<a class="prev" href="' . $this->pageFuncStr($up_row) . '">' . $this->config['prev'] . '</a>' : '';

			$down_row  = $this->nowPage + 1;
			$down_page = ($down_row <= $this->totalPages) ? '<a class="page_text page_next" href="' . $this->pageFuncStr($down_row) . '">' . $this->config['next'] . '</a>' : '';

			$the_first = '<a class="page_text page_home" href="javascript:' . $this->pageFuncStr(1) .'">' . $this->config['first'] . '</a>';

			$the_end = '<a class="page_text page_end" href="' . $this->pageFuncStr($this->totalPages) . '">尾页</a>';
		} else {
			$gofunc = "";

			$up_row  = $this->nowPage - 1;
			$up_page = $up_row > 0 ? '<a class="prev" href="' . $this->url($up_row) . '">' . $this->config['prev'] . '</a>' : '';

			$down_row  = $this->nowPage + 1;
			$down_page = ($down_row <= $this->totalPages) ? '<a class="page_text page_next" href="' . $this->url($down_row) . '">' . $this->config['next'] . '</a>' : '';

			$the_first = '<a class="page_text page_home" href="' . $this->url(1) . '">' . $this->config['first'] . '</a>';

			$the_end = '<a class="page_text page_end" href="' . $this->url($this->totalPages) . '">尾页</a>';
		}


        $link_page = "";
        for ($i = 1; $i <= $this->rollPage; $i++) {
            if (($this->nowPage - $now_cool_page) <= 0) {
                $page = $i;
            } elseif (($this->nowPage + $now_cool_page - 1) >= $this->totalPages) {
                $page = $this->totalPages - $this->rollPage + $i;
            } else {
                $page = $this->nowPage - $now_cool_page_ceil + $i;
            }
            if ($page > 0 && $page != $this->nowPage) {

                if ($page <= $this->totalPages) {
                	if ($this->needForm) {
						$link_page .= '<a class="num" href="' . $this->url($page) . '">' . $page . '</a>';
					} else {
						$link_page .= '<a class="num" href="' . $this->pageFuncStr($page) . '">' . $page . '</a>';
					}
                } else {
                    break;
                }
            } else {
                if ($page > 0) {
                    $link_page .= '<a class="page_current">' . $page . '</span>';
                }
            }
        }
		if (!$this->needForm) {
			$theme = $this->config['theme2'];
		} else {
			$theme = $this->config['theme'];
		}

        $page_str = str_replace(
            array(
            	'%HEADER%',
				'%NOW_PAGE%',
				'%UP_PAGE%',
				'%DOWN_PAGE%',
				'%FIRST%',
				'%LINK_PAGE%',
				'%END%',
				'%TOTAL_ROW%',
				'%TOTAL_PAGE%',
				'%PAGE%',
				'%GO_FUNC%',
				'%PageSize%'
			),
            array(
            	$this->config['header'],
				$this->nowPage,
				$up_page,
				$down_page,
				$the_first,
				$link_page,
				$the_end,
				$this->totalRows,
				$this->totalPages,
				$this->nowPage,
				$gofunc,
				$this->pagesizeSelect()),
            $theme);
        return "{$page_str}" . $this->javascript();
    }

}
