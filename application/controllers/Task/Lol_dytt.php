<?php
class Lol_dytt extends MY_Controller {
	public function test($lol_url){
		$lol_url = str_replace(':', '/', $lol_url);
        $this->load->service('Lol_craw_service');
        $this->Lol_craw_service->craw($lol_url);
        return;
	}

	public function craw_films_by_recom(){
		$start_time = time();
		$page = 0;
		$limit = 50;
		$total = $fail = $success = 0;
		$this->load->service('Lol_craw_service');
		$this->load->model('Lol_recom_model');
		while($page++ < 1){
			$un_crawed_urls = $this->Lol_recom_model->get_un_crawed_urls($fail , $limit);
			$total += count($un_crawed_urls);

			if(empty($un_crawed_urls)){
				f_echo('end ' . $page);
				break;
			}

			foreach($un_crawed_urls as $tmp){
				$lol_url = $tmp['lol_url'];
				f_echo('no exist:' . $lol_url);
				if($this->Lol_craw_service->craw($lol_url)){
					$success++;
					f_echo('success:' . $lol_url);
				}else{
					$fail++;
					$this->Lol_recom_model->incr_invalid_times($lol_url);
					f_echo('fail:' . $lol_url);
				}
			}
		}

		f_echo("end. cost " . (time() - $start_time) . ":" . $total . "-" . $success . "-" . $fail);
	}

	/**
	 * 每日爬取最近更新的条目
	 * @param $day_length
	 */
	public function craw_update($day_length){
		if(!intval($day_length)){
			return;
		}

		$this->load->service('Lol_craw_service');

		$start_time = time();

		$updated_urls = $this->_craw_updated_urls($day_length);
		if(!empty($updated_urls)){
			foreach($updated_urls as $url){
				$this->_c_echo('find updated item :' . $url);
				if($this->Lol_craw_service->craw($url)){
					$this->_c_echo('success' . $url);
				}else{
					$this->_c_echo('fail' . $url);
				}
			}
		}else{
			$this->_log_error('craw nothing updated');
		}

		$this->_c_echo("end. cost " . (time() - $start_time));
	}

	/**
	 * php index.php Task Lol_dytt hand_process 'Anime:JJDJRDEJ'
	 * 手动爬取
	 * @param $lol_url
	 */
	public function hand_process($lol_url){
		$lol_url = str_replace(':', '/', $lol_url);
		$this->load->service('Lol_craw_service');
		$this->Lol_craw_service->craw($lol_url);
	}

	/************************************************* private methods *************************************************************/

	/**
	 * 获取最近几天更新的条目
	 * @param $day_length
	 * @return array
	 */
	private function _craw_updated_urls($day_length){
		$res = array();
		if(!intval($day_length)){
			return $res;
		}

		$days = array();
		for($i = 0; $i < $day_length; $i++){
			$days[] = date('m-d', time() - $i * 86400);
		}

		$html = $this->_get_film_html('http://www.loldytt.com/');
		if(strlen($html) > 300){
			$pattern = '#<li><p>(<em>)?(' . implode('|', $days) . ')([\s\S]*)</a></li>#U';
			$matches = array();
			preg_match_all($pattern, $html, $matches);
			if(!empty($matches) && !empty($matches[0])){
				$pattern = '#<a href="http://www.loldytt.com/([a-zA-Z/()0-9]*)">#U';
				foreach($matches[0] as $part_html){
					$matches = array();
					preg_match($pattern, $part_html, $matches);
					if(!empty($matches) && !empty($matches[1])){
						$res[] = trim($matches[1], '/');
					}
				}
			}
		}

		return $res;
	}

	/**
	 * 根据url获取Html(有反爬取机制, 需要根据返回的内容拼接url然后进行多次递归组装, 直到返回争取的html)
	 * @param $encryptUrl
	 * @param string $param
	 * @param int $level
	 * @return string
	 */
	private function _get_film_html($encryptUrl, $param = '', $level = 0) {
		$res = '';

		if(empty($encryptUrl) || $level >= 250) {
			return $res;
		}

		$cookie_file_path = './lol_cookie.txt';
		static $cookie_time;
		if(empty($cookie_time) || (time() - $cookie_time) > 3){
			$cookie_time = time();
			file_put_contents($cookie_file_path, '');
		}

		$html = $param == ''? f_curl($encryptUrl, array(), $cookie_file_path):f_curl($encryptUrl . '?' . $param, array(), $cookie_file_path);
		if(strlen($html) > 500) {
			$res = iconv(mb_detect_encoding($html,array('UTF-8','GBK','GB2312')), 'UTF-8', $html);
			return $res;
		}

		$pattern = '#location([\s\S]*)</scri#';
		$matches = array();
		preg_match($pattern, $html, $matches);
		if(!empty($matches) && !empty($matches[1])) {
			$param = $matches[1];
			$tmp = explode('?', $param);
			$param = $tmp[1];
			$param = str_replace(array('"', '+', ';', ' '), '', $param);

			return $this->_get_film_html($encryptUrl, $param, $level + 1);
		}else{
			$errorMsg = __FUNCTION__ . ':' . __LINE__ . ':' . 'no matches.' . $html;
			$this->_log_error($errorMsg);
			return '';
		}
	}
}
