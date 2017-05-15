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
		$limit = 10;
		$this->load->model('Film_model');
		$this->load->model('Film_recom_model');
		$this->load->model('Un_douban_model');
		while($page++ < 100000){
			$un_crawed_douban_ids = $this->Un_douban_model->get($page * $limit, $limit);
			if(empty($un_crawed_douban_ids)){
				echo 'end ' . $page . PHP_EOL;
				// 重新开始
//				$page = 0;
				break;
			}

			foreach($un_crawed_douban_ids as $tmp){
				$douban_id = $tmp['douban_id'];
				echo 'no exist:' . $douban_id . PHP_EOL;
				if($this->_craw_and_store_douban_film($douban_id)){
					echo 'success:' . $douban_id . PHP_EOL;
				}else{
					echo 'fail:' . $douban_id . PHP_EOL;
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
				$this->_craw_and_store_douban_film($douban_id);
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
					if($this->_craw_and_store_douban_film($douban_id)){
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
	 * 爬取并存储豆瓣电影(爬取、入库、处理图片、处理海报)
	 * @param $douban_id
	 * @return bool
	 */
	private function _craw_and_store_douban_film($douban_id){
		$this->load->model('Film_model');
		$this->load->service('Film_service');

		$db_film_detail = $this->Film_model->get_by_douban_id($douban_id);

		// 爬取
		$douban_film_detail = $this->_craw_douban_detail($douban_id);
		if(empty($douban_film_detail)){
			$this->_log_error('craw get nothing from ' . $douban_id);
			return false;
		}

		// insert film
		$insert_film_data = array(
			'douban_id' => $douban_film_detail['id'],
			'ch_name' => !empty($douban_film_detail['ch_name'])? $douban_film_detail['ch_name']:'',
			'or_name' => !empty($douban_film_detail['or_name'])? $douban_film_detail['or_name']:'',
			'year' => !empty($douban_film_detail['year'])? $douban_film_detail['year']:'',
			'director' => !empty($douban_film_detail['director'])? $douban_film_detail['director']:'',
			'actors' => !empty($douban_film_detail['actors'])? implode(',', $douban_film_detail['actors']):'',
			'genre' => !empty($douban_film_detail['genre'])? implode(',', $douban_film_detail['genre']):'',
			'genre_p' => !empty($douban_film_detail['genre'])? $this->_cal_genre_product($douban_film_detail['genre']):1,
			'runtime' => !empty($douban_film_detail['runtime'])? $douban_film_detail['runtime']:'',
			'douban_rate' => !empty($douban_film_detail['rate'])? $douban_film_detail['rate']:'',
			'summary' => !empty($douban_film_detail['summary'])? $douban_film_detail['summary']:'',
			'comments' => !empty($douban_film_detail['comments'])? json_encode($douban_film_detail['comments']):'',
			'recom_douban_id' => !empty($douban_film_detail['recomm_ids'])? implode(',', $douban_film_detail['recomm_ids']):'',
		);

		$film_id = null;
		if(empty($db_film_detail)){
			$film_id = $this->Film_model->insert($insert_film_data);
			if(empty($film_id)){
				$this->_log_error('insert db fail ' . $douban_id);
				return false;
			}
		}else{
			$film_id = $db_film_detail['id'];
			$this->Film_model->update_by_douban_id($douban_id, $insert_film_data);
		}

		if($film_id){
			// process names
			$insert_names = array();
			if(!empty($douban_film_detail['other_names'])){
				$insert_names = $douban_film_detail['other_names'];
			}
			$insert_names[] = $douban_film_detail['ch_name'];
			if(!empty($douban_film_detail['or_name'])){
				$insert_names[] = $douban_film_detail['or_name'];
			}
			$this->Film_service->process_film_names($film_id, $insert_names);

			// handle pic
			if(!empty($douban_film_detail['related_pics'])){
				$this->load->service('Film_pic_service');
				$this->Film_pic_service->add_film_pics($film_id,  $douban_film_detail['related_pics']);
			}

			// handle post cover
			if(!empty($douban_film_detail['post_cover'])){
				$this->load->service('Film_pic_service');
				$this->Film_pic_service->update_post_cover($film_id, $douban_film_detail['post_cover']);
			}

			// handle recom
			if(!empty($douban_film_detail['recomm_ids'])){
				$this->load->service('Film_recom_service');
				$this->Film_recom_service->process_douban_recom($film_id, $douban_film_detail['recomm_ids']);
			}

			// 标记已抓取
			$this->load->service('Film_recom_service');
			$this->Film_recom_service->up_un_douban($douban_id);
		}else{
			return false;
		}

		return true;
	}

	/**
	 * 计算类型乘积
	 * @param $genre_desc_arr
	 * @return int
	 */
	private function _cal_genre_product($genre_desc_arr){
		$genre_p = 1;
		if(empty($genre_desc_arr)){
			return $genre_p;
		}

		$this->load->Model('Genre_model');
		$genre_dic = $this->config->item('film_genre_dic');
		foreach($genre_desc_arr as $genre_desc){
			if(empty($genre_dic[$genre_desc])){
				$xx = end($genre_dic);
				$g_dic = array(
					'genre_id' => empty($genre_dic)? get_closest_prime(0):get_closest_prime($xx['genre_id']),
					'desc' => $genre_desc,
				);
				$this->Genre_model->insert($g_dic);
				$genre_dic[$genre_desc] = $g_dic;
			}
			$genre_p *= $genre_dic[$genre_desc]['genre_id'];
		}

		$this->config->set_item('film_genre_dic', $genre_dic);

		return $genre_p;
	}

	/**
	 * 爬取豆瓣详情页
	 * @param $douban_id
	 * @return array
	 */
	private function _craw_douban_detail($douban_id, $attrs = array()) {
		$ret = array();

		if(empty($douban_id)) {
			return $ret;
		}

		//$html = file_get_contents('/home/wangchuanchuan/tmp/douban_detail.html');
		$douban_link = "https://movie.douban.com/subject/{$douban_id}/";
		$retry = 3;
		$html = '';
		while($retry--){
			$html = $this->_request_douban_login($douban_link);
			if(strlen($html) < 300 || strpos($html, '你想访问的页面不存在') !== false) {
				continue;
			}else{
				break;
			}
		}
		if(strlen($html) < 300 || strpos($html, '你想访问的页面不存在') !== false) {
			$this->_log_error('豆瓣页面不存在:' . $douban_id);
			return $ret;
		}

		//
		$ret['link'] = $douban_link;
		$ret['id'] = $douban_id;

		// 主名称
		if(empty($attrs) || in_array('ch_name', $attrs)){
			$pattern = '#<span property="v:itemreviewed">([\s\S]*)</span>#U';
			$matches = array();
			preg_match($pattern, $html, $matches);
			if(!empty($matches[1])) {
				$index = strpos($matches[1], ' ');
				if($index === false) {
					$ret['ch_name'] = $or_name = trim($matches[1]);
				}else {
					$ret['ch_name'] = trim(substr($matches[1], 0, $index));
					$ret['or_name'] = trim(substr($matches[1], $index));
				}
			}

			if(empty($ret['ch_name'])) {
				$this->_log_error('no ch_name on ' . $douban_id);
			}
		}

		// 海报
		if(empty($attrs) || in_array('post_cover', $attrs)){
			$pattern = '#<div id="mainpic"([\s\S]*)</div>#U';
			$matches = array();
			preg_match($pattern, $html, $matches);
			if(!empty($matches[1])) {
				$post_cover_html = $matches[1];
				$pattern = '#src="([\s\S]*)"#U';
				$matches = array();
				preg_match($pattern, $post_cover_html, $matches);
				if(!empty($matches)){
					$ret['post_cover'] = $matches[1];
				}
			}
			if(empty($ret['post_cover'])) {
				$this->_log_error('no post_cover ' . $douban_id);
			}
		}

		// 年份
		if(empty($attrs) || in_array('year', $attrs)){
			$pattern = '#<span class="year">\(([\s\S]*)\)</span>#U';
			$matches = array();
			preg_match($pattern, $html, $matches);
			if(!empty($matches[1])) {
				$ret['year'] = $matches[1];
			}

			if(empty($ret['year'])) {
				$this->_log_error('no year ' . $douban_id);
			}
		}

		// 其他名称
		if(empty($attrs) || in_array('other_names', $attrs)){
			$pattern = '#<span class="pl">又名:</span>([\s\S]*)<br/>#U';
			$matches = array();
			preg_match($pattern, $html, $matches);
			if(!empty($matches[1])) {
				$names = explode('/', $matches[1]);
				$ret['other_names'] = $names;
			}
		}

		// 导演
		if(empty($attrs) || in_array('director', $attrs)){
			$pattern = '#rel="v:directedBy">([\s\S]*)</a>#U';
			$matches = array();
			preg_match($pattern, $html, $matches);
			if(!empty($matches[1])) {
				$ret['director'] = $matches[1];
			}
		}


		// 主演
		if(empty($attrs) || in_array('actors', $attrs)){
			$pattern = '#<span class="actor">([\s\S]*)<br/>#U';
			$matches = array();
			preg_match($pattern, $html, $matches);
			if(!empty($matches[1])) {
				$actors_html = $matches[1];
				$pattern = '#rel="v:starring">([\s\S]*)</a>#U';
				$matches = array();
				preg_match_all($pattern, $actors_html, $matches);
				if(!empty($matches[1])) {
					$ret['actors'] = $matches[1];
				}
			}
		}

		// 类型
		if(empty($attrs) || in_array('genre', $attrs)){
			$pattern = '#<span property="v:genre">([\s\S]*)</span>#U';
			$matches = array();
			preg_match_all($pattern, $html, $matches);
			if(!empty($matches[1])) {
				$ret['genre'] = $matches[1];
			}
		}

		// 片长
		if(empty($attrs) || in_array('runtime', $attrs)){
			$pattern = '#<span property="v:runtime" content="(\d+)">#U';
			$matches = array();
			preg_match($pattern, $html, $matches);
			if(!empty($matches[1])) {
				$ret['runtime'] = $matches[1] . '分钟';
			}
		}

		// 评分
		if(empty($attrs) || in_array('rate', $attrs)){
			$pattern = '#property="v:average">(\d.\d)</strong>#U';
			$matches = array();
			preg_match($pattern, $html, $matches);
			if(!empty($matches[1])) {
				$ret['rate'] = $matches[1];
			}
		}

		// 简介
		if(empty($attrs) || in_array('summary', $attrs)){
			$pattern = '#<span property="v:summary" class="">([\s\S]*)</span>#U';
			$matches = array();
			preg_match($pattern, $html, $matches);
			if(!empty($matches[1])) {
				$ret['summary'] = trim($matches[1]);
			}
		}

		// 相关图片
		if(empty($attrs) || in_array('related_pics', $attrs)){
			$pattern = '#<ul class="related-pic-bd([\s\S]*)</ul>#U';
			$matches = array();
			preg_match($pattern, $html, $matches);
			if(!empty($matches[1])) {
				$related_pics_html = trim($matches[1]);
				$pattern = '#<img src="([\s\S]*)"#U';
				$matches = array();
				preg_match_all($pattern, $related_pics_html, $matches);
				if(!empty($matches[1])) {
					$ret['related_pics'] = array_slice($matches[1], 1);
				}
			}
		}

		// 获奖情况
		if(empty($attrs) || in_array('awards', $attrs)){
			$pattern = '#<ul class="award">([\s\S]*)</ul>#U';
			$matches = array();
			preg_match_all($pattern, $html, $matches);
			if(!empty($matches[1])) {
				$awards = array();
				foreach($matches[1] as $tmp) {
					$pattern = '#<li>([\s\S]*)</li>#U';
					$matches = array();
					preg_match_all($pattern, $tmp, $matches);
					if(count($matches[1]) === 3) {
						$award = array();
						$award['award'] = substr($matches[1][0], stripos($matches[1][0], '>'), strrpos($matches[1][0], '<') - stripos($matches[1][0], '>') );
						$award['type'] = $matches[1][1];
						$award['who'] = substr($matches[1][2], stripos($matches[1][2], '>'), strrpos($matches[1][2], '<') - stripos($matches[1][2], '>') );
						if(!empty($award)) $awards[] = $award;
					}
				}
				$ret['awards'] = $awards;
			}
		}

		// 推荐
		if(empty($attrs) || in_array('recomm_ids', $attrs)){
			$pattern = '#<div class="recommendations-bd">([\s\S]*)</div>#U';
			$matches = array();
			preg_match($pattern, $html, $matches);
			if(!empty($matches[1])) {
				$recomm_html = trim($matches[1]);
				$pattern = '#href="https://movie.douban.com/subject/(\d+)/\?from=subject-page"#U';
				$matches = array();
				preg_match_all($pattern, $recomm_html, $matches);
				if(!empty($matches[1])) {
					$ret['recomm_ids'] = $matches[1];
				}
			}
		}

		// 评论
		if(empty($attrs) || in_array('comments', $attrs)){
			$pattern = '#<div class="comment">([\s\S]*)</div>#U';
			$matches = array();
			preg_match_all($pattern, $html, $matches);
			if(!empty($matches[1])) {
				$comments = array();
				foreach($matches[1] as $comment_html) {
					$tmp_comment = array();
					$pattern = '#class="">([\s\S]*)</a>#U';
					$matches = array();
					preg_match($pattern, $comment_html, $matches);
					if(!empty($matches[1])) {
						$tmp_comment['user'] = $matches[1];
					}

					$pattern = '#<p class=""> ([\s\S]*)</p>#U';
					$matches = array();
					preg_match($pattern, $comment_html, $matches);
					if(!empty($matches[1])) {
						$tmp_comment['content']= $matches[1];
					}

					if(!empty($tmp_comment['content'])) {
						$comments[] = $tmp_comment;
					}
				}

				$ret['comments'] = $comments;
			}
		}

		return $ret;
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
