<?php
require_once(APPPATH . 'service/parser/Parser_base.php');

class Parser_douban extends Parser_base{

	public function __construct(){
		parent::__construct();
		$this->load->service('crawler/Crawler_douban');
	}

	public function process($douban_id){
		$html = $this->Crawler_douban->process($douban_id);
		if(empty($html)){
			return false;
		}

		$dom_els = $this->_parse_dom_els($html);
		$des_html = $this->_pack_des_html($dom_els['head'], $dom_els['body']);
		file_put_contents($this->cal_path($douban_id), $des_html);

		return true;
	}

	/**
	 * 计算路径
	 * @param $douban_id
	 * @return string
	 */
	public function cal_path($douban_id){
		$md5 = md5($douban_id);
		$dir = APPPATH . 'data/douban/' . implode('/', array($md5[29], $md5[30], $md5[31])) . '/' ;
		if(!is_dir($dir)){
			mkdir($dir, 0776, true);
		}
		$path = $dir . $douban_id . '.html';
		return $path;
	}

	/**
	 * 判断是否已经抓取过
	 * @param $douban_id
	 * @return bool
	 */
	public function exist($douban_id){
		$path = $this->cal_path($douban_id);
		return is_file($path);
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