<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Film extends MY_Controller {

	public function index()
	{
		$search_words = $this->input->get('film_name');
		$search_result = array();
		if(!empty($search_words)) {
			$this->load->model('Film_model');
			$search_result = $this->Film_model->query_by_name($search_words);
		}

		if(!empty($search_result)) {
			foreach($search_result as &$tmp) {
				$tmp['actors'] = str_replace(',', '/', $tmp['actors']);
				$tmp['other_names'] = !empty($tmp['other_names'])? json_decode($tmp['other_names'], true):array();
				$tmp['summary'] = mb_substr($tmp['summary'], 0, 100);
			}
		}

		$this->assign('data', array(
			'search_words' => $search_words,
			'search_res' => $search_result,
		));

		$this->display('home.tpl');
	}

	public function detail()
	{
		$id = $this->input->get('id');
		$this->load->model('Film_model');
		$this->load->model('Film_pic_model');
		$this->load->model('Film_bt_model');
		$film_detail = $this->Film_model->get_detail_by_id($id);

		if(!empty($film_detail)) {
			$film_detail['actors'] = str_replace(',', '/', $film_detail['actors']);
			$film_detail['genre'] = str_replace(',', '/', $film_detail['genre']);
			$film_detail['other_names'] = !empty($film_detail['other_names'])? json_decode($film_detail['other_names'], true):array();
			$film_detail['comments'] = !empty($film_detail['comments'])? json_decode($film_detail['comments'], true):array();
			$film_detail['related_pics'] = $this->Film_pic_model->get_pics_by_douban_id($film_detail['douban_id']);
			if(!empty($film_detail['recom_douban_id'])) {
				$film_detail['recom_films'] = $this->Film_model->get_recom_films($film_detail['douban_id']);
			}

			$bts = $this->Film_bt_model->get_by_douban_id($film_detail['douban_id']);
			$sorted_bts = array(
				'thunder' => array(),
				'bt' => array(),
				'mag' => array(),
			);
			if(!empty($bts)){
				foreach($bts as $tmp){
					if($tmp['type'] == 1){
						array_push($sorted_bts['thunder'], $tmp);
					}else if($tmp['type'] == 2){
						array_push($sorted_bts['bt'], $tmp);
					}else if($tmp['type'] == 3){
						array_push($sorted_bts['mag'], $tmp);
					}
				}
			}
			$film_detail['bt'] = $sorted_bts;
		}

		$this->assign('data', array(
			'title' => $film_detail['ch_name'],
			'film_detail' => $film_detail,
		));
		$this->display('film_detail.tpl');
	}

}
