<?php
class Crawler_douban extends MY_Service{

	public function __construct(){
		parent::__construct();

		$this->load->service('parser/Parser_douban');
	}

	public function process($douban_id){
		if(empty($douban_id)){
			return false;
		}
		$detail_html = $this->_get_douban_detail_html($douban_id);
		if(empty($detail_html)){
			return false;
		}

		$this->Parser_douban->process($douban_id, $detail_html);
	}

	/**************************************private methods****************************************************************************/

	/**
	 * 获取豆瓣详情页
	 * @param $douban_id
	 * @return mixed|string
	 */
	private function _get_douban_detail_html($douban_id){
		$detail_html = '';

		if(empty($douban_id)){
			return $detail_html;
		}

		$douban_link = "https://movie.douban.com/subject/{$douban_id}/";
		$retry = 1;
		while($retry--){
			$html = $this->_request_douban($douban_link);
			if(!$this->_check_for_douban_detail_html($html)) {
				continue;
			}else{
				$detail_html = $html;
				break;
			}
		}

		return $detail_html;
	}

}