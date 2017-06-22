<?php
class Parser_douban extends MY_Service{

	/**
	 * @param $douban_id
	 * @param $html
	 */
	public function process($douban_id, $html){
		$dom_els = $this->_parse_dom_els($html);
		$des_html = $this->_pack_des_html($dom_els['head'], $dom_els['body']);
		file_put_contents($this->_cal_path($douban_id), $des_html);
	}

	/**
	 * 计算路径
	 * @param $douban_id
	 * @return string
	 */
	public function _cal_path($douban_id){
		$md5 = md5($douban_id);
		$dir = APPPATH . 'data/douban/' . implode('/', array($md5[29], $md5[30], $md5[31])) . '/' ;
		if(!is_dir($dir)){
			mkdir($dir, 0776, true);
		}
		$path = $dir . $douban_id . '.html';
		return $path;
	}

	/**************************************private methods****************************************************************************/

	/**
	 * @param DOMDocument $doc
	 * @param $class_name
	 * @param $tag
	 * @return DOMElement
	 */
	private function _get_element_by_class(DOMDocument $doc, $tag, $class_name){
		$node_arr = array();
		$domnode_list = $doc->getElementsByTagName($tag);
		if(!empty($domnode_list)){
			for($i = 0; $i < $domnode_list->length; ++$i){
				$node_class = $domnode_list->item($i)->getAttribute('class');
				if(!empty($node_class) && $node_class == $class_name){
					$node_arr[] = $domnode_list->item($i);
				}
			}
		}

		return count($node_arr) == 1? $node_arr[0]:null;
	}

	/**
	 * @param DOMDocument $doc
	 * @param $tag_name
	 * @return DOMElement|null
	 */
	private function _get_unique_element_by_tag(DOMDocument $doc, $tag_name){
		$node_list = $doc->getElementsByTagName($tag_name);
		return $node_list->length == 1? $node_list->item(0):null;
	}

	/**
	 * 组装解析完的html
	 * @param $head_dom_el_arr
	 * @param $body_el_arr
	 * @return string
	 */
	private function _pack_des_html($head_dom_el_arr, $body_el_arr){
		$html = <<<HTML
	<!DOCTYPE html>
	<html lang="zh-cmn-Hans" class="ua-windows ua-webkit">
    <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
HTML;

		foreach($head_dom_el_arr as $tmp_dom_el){
			if(!empty($tmp_dom_el)){
				$html .= PHP_EOL . $tmp_dom_el->C14N();
			}
		}
		$html .= PHP_EOL . '</head>' . PHP_EOL . '<body>';

		foreach($body_el_arr as $tmp_dom_el){
			if(!empty($tmp_dom_el)){
				$html .= PHP_EOL . $tmp_dom_el->C14N();
			}
		}
		$html .= PHP_EOL . '</body>' . PHP_EOL . '</html>';

		return $html;
	}

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