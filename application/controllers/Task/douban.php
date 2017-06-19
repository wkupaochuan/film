<?php
class Douban extends MY_Controller {
	private $douban_login_cookie = '/tmp/douban_login_cookie.txt';
	private $_login = false;

    public function __construct(){
        parent::__construct();
        $this->load->service('Douban_service');
    }

	public function test($douban_id){
		$this->load->service('Douban_service');
		$this->Douban_service->craw_comments($douban_id);
		return;
		$page = 0;
		$limit = 20;
		$this->load->model('Film_model');
		$this->load->model('Film_name_model');
		$this->load->model('Film_bt_model');
		$this->load->model('Film_pic_model');
		$this->load->model('Film_recom_model');

		while($page < 100000){
			$films = $this->Film_model->get($page++ * $limit, $limit);

			if(empty($films)){
				echo 'end ' . $page . PHP_EOL;
				break;
			}

			echo $page . PHP_EOL;

			foreach($films as $tmp){
				$film_id = $tmp['id'];
				$douban_id = $tmp['douban_id'];
				$this->Film_bt_model->update_by_douban_id($douban_id, $film_id);
				$this->Film_name_model->update_by_douban_id($douban_id, $film_id);
				$this->Film_pic_model->update_by_douban_id($douban_id, $film_id);
				$this->Film_recom_model->update_by_douban_id($douban_id, $film_id);
			}
		}
	}

	public function craw_comments(){

		$page = 0;
		$limit = 2;
		$this->load->model('Film_model');
		$this->load->service('Douban_service');

		while($page < 5){
			$films = $this->Film_model->get($page++ * $limit, $limit);

			if(empty($films)){
				f_echo('end ' . $page );
				break;
			}

			f_echo($page);

			foreach($films as $tmp){
				$douban_id = $tmp['douban_id'];
				$this->Douban_service->craw_comments($douban_id);
			}
		}
	}

	public function craw_douban_films_by_db_recom_ids($login){
		if($login == 1){
			$this->_login = true;
		}
		$page = 0;
		$limit = 20;
		$fail = 0;
		$this->load->model('Film_model');
		$this->load->model('Film_recom_model');
		$this->load->model('Un_douban_model');
		while($page++ < 100000){
			$un_crawed_douban_ids = $this->Un_douban_model->get($fail, $limit);
			if(empty($un_crawed_douban_ids)){
				echo 'end ' . $page . PHP_EOL;
				// 重新开始
//				$page = 0;
				break;
			}

			foreach($un_crawed_douban_ids as $tmp){
				$douban_id = $tmp['douban_id'];
				echo 'no exist:' . $douban_id . PHP_EOL;
				if($this->Douban_service->craw_and_store_douban_film($douban_id)){
					echo 'success:' . $douban_id . PHP_EOL;
				}else{
					echo 'fail:' . $douban_id . PHP_EOL;
					$fail++;
				}
			}
		}
	}

	public function hand_process_douban($type, $str){
		$douban_id_arr = array();
//		$this->_login();
		if($type == 1){
			$douban_id_arr = explode(':', $str);
		}else if($type == 2){
			$file_content = file_get_contents(str_replace(':', '/', $str));
			$douban_id_arr = explode("\n", $file_content);
		}

		foreach($douban_id_arr as $douban_id){
			if(!empty($douban_id)){
				$this->Douban_service->craw_and_store_douban_film($douban_id);
			}
		}
	}

	public function craw_daily(){
		$url_arr = array(
			"movive_recom" => "https://movie.douban.com/j/search_subjects?type=movie&tag=%E7%83%AD%E9%97%A8&sort=recommend&page_limit=20&page_start=0",
			"movive_time" => "https://movie.douban.com/j/search_subjects?type=movie&tag=%E7%83%AD%E9%97%A8&sort=time&page_limit=20&page_start=0",
			"tv_recom" => "https://movie.douban.com/j/search_subjects?type=tv&tag=%E7%83%AD%E9%97%A8&sort=recommend&page_limit=20&page_start=0",
			"tv_time" => "https://movie.douban.com/j/search_subjects?type=tv&tag=%E7%83%AD%E9%97%A8&sort=time&page_limit=20&page_start=0",
		);

		foreach($url_arr as $key => $url){
			$douban_ids = $this->_craw_updated_items($url);
			$this->_c_echo($key . ':' . implode(',', $douban_ids));
			if(!empty($douban_ids)){
				foreach($douban_ids as $douban_id){
					if($this->Douban_service->craw_and_store_douban_film($douban_id)){
						$this->_c_echo('success ' . $douban_id);
					}else{
						$this->_c_echo('fail ' . $douban_id);
					}
				}
			}
		}
	}

	/**
	 * 登陆并保存登陆后的cookie
	 * @param string $cp
	 * @param string $cp_id
	 */
	public function login($cp = '', $cp_id = ''){
		$this->Douban_service->login($cp, $cp_id);
	}

    public function overwrite(){
        $this->Douban_service->overwrite_names();
    }

	/************************************************* private methods *************************************************************/

	/**
	 * 获取更新的条目
	 * @param $url
	 * @return array
	 */
	private function _craw_updated_items($url){
		$douban_ids = array();
		$res_str = $this->_request_douban_login($url);
		if(!empty($res_str)){
			$items = json_decode($res_str, true);
			if(!empty($items) && !empty($items['subjects'])){
				foreach($items['subjects'] as $item){
					$douban_ids[] = $item['id'];
				}
			}
		}

		return $douban_ids;
	}


	/**
	 * 采用登陆后的cookie来请求
	 * @param $url
	 * @param array $post_data
	 * @return mixed|string
	 */
	private function _request_douban_login($url, $post_data = array()){
		static $r_time;
		$s_intval = rand(3,10);
		if(!empty($r_time) && (time() - $r_time) > $s_intval ){
			sleep(1);
		}

		$header = array(
			'Accept' => '*/*',
		    'Accept-Encoding' => 'gzip, deflate',
		    'Accept-Language' => 'zh-CN,zh;q=0.8',
		    'Connection' => 'keep-alive',
		    'Referer' => 'https => //accounts.douban.com/login?alias=*******略',
		    'User-Agent' => 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/45.0.2454.101 Safari/537.36',
		);
		return f_curl($url, $post_data, $this->douban_login_cookie, $header);
	}
}
