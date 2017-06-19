<?php
class Lol_dytt extends MY_Controller {

	public function __construct(){
		parent::__construct();
		$this->load->service('Lol_craw_service');
	}

	public function test($lol_url){
		$lol_url = str_replace(':', '/', $lol_url);
        $this->Lol_craw_service->craw($lol_url);
        return;
	}

	public function craw_films_by_recom(){
		$start_time = time();
		$page = 0;
		$limit = 50;
		$total = $fail = $success = 0;
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

		$start_time = time();
		$this->Lol_craw_service->craw_up_films($day_length);
		$this->_c_echo("end. cost " . (time() - $start_time));
	}

	/**
	 * php index.php Task Lol_dytt hand_process 'Anime:JJDJRDEJ'
	 * 手动爬取
	 * @param $lol_url
	 */
	public function hand_process($lol_url){
		$lol_url = str_replace(':', '/', $lol_url);
		$this->Lol_craw_service->craw($lol_url);
	}
}
