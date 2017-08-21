<?php
class Crawler_s80 extends MY_Service{

	public function __construct(){
		parent::__construct();
	}

	/**
	 * 详情页
	 * @param $film_url
	 * @return mixed|string
	 */
	public function detail($film_url){
		$html = '';
		if(empty($film_url)){
			return $html;
		}
		$html = $this->_get_detail_html($film_url);

		return $html;
	}

	/**
	 * 下载链接html
	 */
	public function bt($bt_url){
		$html = '';
		if(empty($bt_url)){
			return $html;
		}

		$html = $this->_get_bt_html($bt_url);

		return $html;
	}

	/**************************************private methods****************************************************************************/

	/**
	 * 获取详情页
	 * @param $film_url
	 * @return mixed|string
	 */
	private function _get_detail_html($film_url){
		$detail_html = '';

		if(empty($film_url)){
			return $detail_html;
		}

		$douban_link = "http://www.80s.tw/{$film_url}";
		$retry = 1;
		while($retry--){
			$html = $this->_request_s80($douban_link);
			if(!$this->_check_detail_html($html)) {
				continue;
			}else{
				$detail_html = $html;
				break;
			}
		}

		return $detail_html;
	}

	/**
	 * 采用登陆后的cookie来请求
	 * @param $url
	 * @param array $post_data
	 * @return mixed|string
	 */
	private function _request_s80($url, $post_data = array()){
		$header = array(
			'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
			'Accept-Encoding' => 'gzip, deflate',
			'Accept-Language' => 'zh-CN,zh;q=0.8',
			'Connection' => 'keep-alive',
			'Referer' => 'http://www.80s.tw/',
			'User-Agent' => 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/45.0.2454.101 Safari/537.36',
		);
		$cookie_file_path = '/tmp/80s_cookie.txt';

		return f_curl($url, $post_data, $cookie_file_path, $header);
	}

	/**
	 * 校验有效的详情页
	 * @param $html
	 * @return bool
	 */
	private function _check_detail_html($html){
		if(empty($html)){
			return false;
		}

		return strpos($html, '<div class="info">') !== false;
	}

	/**
	 * 获取下载资源html
	 * @param $bt_url
	 * @return mixed|string
	 */
	private function _get_bt_html($bt_url){
		$detail_html = '';

		if(empty($bt_url)){
			return $detail_html;
		}

		$bt_url = ltrim($bt_url, '/');
		$link = "http://www.80s.tw/{$bt_url}";
		$retry = 1;
		while($retry--){
			$html = $this->_request_s80($link);
			if(strlen($html)) {
				continue;
			}else{
				$detail_html = $html;
				break;
			}
		}

		return $detail_html;
	}

}