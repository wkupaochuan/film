<?php
class Crawler_douban extends MY_Service{

	public function __construct(){
		parent::__construct();
	}

	public function process($douban_id){
		$html = '';
		if(empty($douban_id)){
			return $html;
		}
		$html = $this->_get_douban_detail_html($douban_id);

		return $html;
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

	/**
	 * 校验是否没有更多评论了
	 * @param $html
	 * @return bool
	 */
	private function _check_end_for_comment($html){
		$ret = array(
			'end' => false,
			'start' => 0,
			'limit' => 0,
		);

		if(empty($html)){
			$ret['end'] = true;
			return $ret;
		}

		$pattern = '#<div id="paginator" class="center">([\s\S]*)</div>#U';
		$matches = array();
		preg_match($pattern, $html, $matches);
		if(!empty($matches) && !empty($matches[1])){
			$block_htlm = $matches[1];
			$pattern = '#<a ([\s\S]*)>([\s\S]*)</a>#U';
			$matches = array();
			preg_match_all($pattern, $block_htlm, $matches);

			if(!empty($matches) && !empty($matches[1])){
				$end_html = '';
				if(count($matches[1]) == 1){
					// 首页
					$end_html = $matches[1][0];
				}else if(count($matches[1]) == 3){
					// 普通页
					$end_html = $matches[1][2];
				}else if(count($matches[1]) == 2){
					// 尾页
					$ret['end'] = true;
				}

				if(!empty($end_html)){
					$pattern = '#start=([\d]+)&amp;limit=([\d]+)&amp;#U';
					$matches = array();
					preg_match($pattern, $end_html, $matches);
					if(!empty($matches) && !empty($matches[1]) && !empty($matches[2])){
						$ret['start'] = $matches[1];
						$ret['limit'] = $matches[2];
					}
				}
			}
		}

		return $ret;
	}

	/**
	 * 获取评论页面html, 带重试3次
	 * @param $url
	 * @return bool
	 */
	private function _get_comments_html($url){
		if(empty($url)){
			return '';
		}
		$retry = 3;
		$html = '';
		while($retry-- >= 0){
			$html = $this->_request_douban($url);
			$pattern = 'class="next">后页';
			if(strpos($html, $pattern) !== false){
				break;
			}else{
				$html = '';
			}
		}

		if(empty($html)){
			f_log_error('get nothing on douban comments url:' . $url);
		}

		return $html;
	}

	/**
	 * 采用登陆后的cookie来请求
	 * @param $url
	 * @param array $post_data
	 * @return mixed|string
	 */
	private function _request_douban($url, $post_data = array()){
		sleep(rand(1,3));

		$header = array(
			'Accept' => '*/*',
			'Accept-Encoding' => 'gzip, deflate',
			'Accept-Language' => 'zh-CN,zh;q=0.8',
			'Connection' => 'keep-alive',
			'Referer' => 'https => //accounts.douban.com/login?alias=*******略',
			'User-Agent' => 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/45.0.2454.101 Safari/537.36',
		);
		$cookie_file_path = '/tmp/douban_login_cookie.txt';

		return f_curl($url, $post_data, $cookie_file_path, $header);
	}

	/**
	 * 校验有效的豆瓣详情页
	 * @param $html
	 * @return bool
	 */
	private function _check_for_douban_detail_html($html){
		if(empty($html)){
			return false;
		}

		return strpos($html, '<div id="mainpic"') !== false;
	}

}