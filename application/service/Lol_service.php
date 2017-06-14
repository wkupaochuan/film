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

	/**************************************private methods****************************************************************************/
}