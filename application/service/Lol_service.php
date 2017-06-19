<?php
class Lol_service extends MY_Service{

	public function __construct(){
		parent::__construct();
		$this->load->model('Lol_film_model');
		$this->load->model('Lol_bt_model');
	}

	public function get_bts($film_id){
		$bts = array();
		if(empty($film_id)){
			return $bts;
		}

		$bts = $this->Lol_bt_model->get_by_film_id($film_id);

		return $bts;
	}

	/**
	 * 格式化数据库中的详情(很多/分割或者json串)
	 * @param $lol_film_detail
	 */
	public function format_db_film_detail(&$lol_film_detail){
		$lol_film_detail['other_names'] = explode('/', $lol_film_detail['other_names'] );
		$lol_film_detail['actors'] = explode('/', $lol_film_detail['actors'] );
		if(!empty($lol_film_detail['actors'])){
			foreach($lol_film_detail['actors'] as $index => &$actor){
				$actor = trim($actor);
				if(empty($actor)){
					unset($lol_film_detail['actors'][$index]);
				}
			}
		}
	}

	/**************************************private methods****************************************************************************/
}