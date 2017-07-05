<?php
require_once(APPPATH . 'service/parser/Parser_base.php');

class Parser_s80 extends Parser_base{

	public function __construct(){
		parent::__construct();
		$this->load->service('crawler/Crawler_s80');
	}

	public function detail($s80_url){
		$html = $this->Crawler_s80->process($s80_url);
		if(empty($html)){
			return false;
		}

		$dom_els = $this->_parse_dom_els($html);
		file_put_contents($this->cal_path($s80_url), $this->_pack_des_html($dom_els['head'], $dom_els['body']));

		return true;
	}

	/**
	 * 计算路径
	 * @param $s80_url
	 * @return string
	 */
	public function cal_path($s80_url){
		$md5 = md5($s80_url);
		$dir = APPPATH . 'data/s80/' . implode('/', array($md5[29], $md5[30], $md5[31])) . '/' ;
		if(!is_dir($dir)){
			mkdir($dir, 0776, true);
		}
		$path = $dir . $md5 . '.html';
		return $path;
	}

	/**
	 * 判断是否已经抓取过
	 * @param $douban_id
	 * @return bool
	 */
	public function exist($douban_id){
		$path = $this->cal_path($douban_id);
		return file_exists($path);
	}

	/**************************************private methods****************************************************************************/

	/**
	 * 解析
	 * @param $html
	 * @return array
	 */
	private function _parse_dom_els($html){
		$doc = new DOMDocument();
		@$doc->loadHTML($html);

		// title
		$title_node = $this->_get_unique_element_by_tag($doc, 'title');

		// top250
		$top250_div_node = $this->_get_element_by_class($doc, 'div', 'top250');

		// h1
		$h1_node = $this->_get_unique_element_by_tag($doc, 'h1');

		// 信息div
		$info_div_node = $this->_get_element_by_class($doc, 'div', 'subjectwrap clearfix');

		// 简介
		$summary_div_node = $this->_get_element_by_class($doc, 'div', 'related-info');

		// 影人
		$celebrities_div_node = $doc->getElementById('celebrities');

		// 图片div
		$pics_div_node = $doc->getElementById('related-pic');

		// 获奖
		$award_div_node = $this->_get_element_by_class($doc, 'div', 'mod');

		// 推荐
		$recom_div_node = $this->_get_element_by_class($doc, 'div', 'recommendations-bd');

		return array(
			'head' => array($title_node),
			'body' => array($top250_div_node, $top250_div_node, $h1_node, $info_div_node, $summary_div_node,
			$celebrities_div_node, $pics_div_node,  $award_div_node,  $recom_div_node )
		);
	}

}