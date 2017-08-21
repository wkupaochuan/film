<?php
require_once(APPPATH . 'service/parser/Parser_base.php');

class Parser_s80 extends Parser_base{

	public function __construct(){
		parent::__construct();
		$this->load->service('crawler/Crawler_s80');
	}

	public function detail($s80_url){
		$html = $this->Crawler_s80->detail($s80_url);
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

		// info
		$info_div_node = get_unique_element_by_class($doc, 'div', 'info');

		// recom
		$recom_ul_node = get_unique_element_by_class($doc, 'ul', 'me1');

		//
		return array(
			'head' => array(),
			'body' => array($info_div_node, $recom_ul_node)
		);
	}

	private function _bts($film_url){
		$bt_url_dic = array(
			'bt-1', // 电视格式
			'bd-1', // 平板mp4
			'hd-1', // 手机mp4
			'mp4-1', // 小mp4
		);

		foreach($bt_url_dic as $tmp_url){
			$tmp_url = $film_url . '/' . $tmp_url;
			$bt_html = $this->Crawler_s80->bt($tmp_url);
		}
	}

}