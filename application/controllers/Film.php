<?php
class Film extends MY_Controller {
	public function __construct(){
		parent::__construct();
		$this->load->service('Film_service');
	}

	public function index()
	{
		$search_words = $this->input->get('film_name');
        $hot_films = array();

		if(!empty($search_words)) {
			$tpl = 'search_home.tpl';
			$this->load->model('Film_model');
			$search_result = $this->Film_model->query_by_name_for_user_search($search_words);
		}else{
			$tpl = 'home.tpl';
			$this->load->service('Film_service');
            $hot_films = $this->Film_service->get_last_week_hot_films();
            if(!empty($hot_films)){
                $hot_films = array_slice($hot_films, 0, count($hot_films) - count($hot_films)%6);
            }
			$search_result = $this->Film_service->get_up_films(strtotime(date('Y-m-d', time() - 86400)), 24);
		}

		if(!empty($search_result)) {
			foreach($search_result as &$tmp) {
				$tmp['actors'] = implode('/', array_slice(explode(',', $tmp['actors']), 0, 3));
				$tmp['other_names'] = !empty($tmp['other_names'])? json_decode($tmp['other_names'], true):array();
				$tmp['summary'] = trim(mb_substr($tmp['summary'], 0, 100));
			}
		}

		$this->assign('data', array(
			'search_words' => $search_words,
			'search_res' => $search_result,
            'hot_films' => $hot_films,
		));

		$this->display($tpl);
	}

	public function detail()
	{
		$id = $this->input->get('id');

		$film_detail = $this->Film_service->get_film_detail($id);

		$this->assign('data', array(
			'title' => $film_detail['ch_name'] . "-下载-迅雷-BT-磁力-电影饭",
			'keywords' => $film_detail['ch_name'] . "," . $film_detail['or_name'] . ",迅雷,bt种子,磁力下载,电影饭" ,
			'description' => "<{$film_detail['ch_name']}>是由" . implode('/', array_slice($film_detail['actors_arr'], 0, 3)) . "等主演的电影, 电影饭搜集网络资源, 汇集迅雷、bt、磁力等下载资源，同时收集了评分、热度、短评等信息。",
			'film_detail' => $film_detail,
		));
		$this->display('film_detail.tpl');
	}

	public function film_list(){
		$page = intval($this->input->get('page'));
		if($page < 0) $page = 0;
		$genre = $this->input->get('genre');
		if(empty($genre)) $genre = 1;
		$this->load->model('Film_model');
		$films = $this->Film_model->query_by_genre($genre, $page * 15, 15);
		if(!empty($films)){
			foreach($films as &$film){
				if(!empty($film['actors'])){
					$film['actors'] = implode('/', array_slice(explode(',', $film['actors']), 0, 6));
				}
			}
		}

		$genre_dic =$this->config->item('film_genre_dic');
		if(!empty($genre_dic)){
			$genre_dic = array_slice($genre_dic, 0, 20);
		}
		$this->assign('data', array(
			'last' => ($page - 1) > 0?  ($page - 1):0,
			'next' => $page + 1,
			'genre' => $genre,
			'genre_dic' => $genre_dic,
			'search_res' => $films,
		));

		$this->display('home_for_spider.tpl');
	}

	/**
	 * 用户订阅
	 */
	public function user_rs(){
		$user_mail = $this->_param('mail');
		$film_id = $this->_param('film_id');

		$this->Film_service->user_rs(array(
			'mail' => $user_mail,
			'film_id' => $film_id
		));
	}
}
