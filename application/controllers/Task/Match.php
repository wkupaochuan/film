<?php

class Match extends  MY_Controller{
	public function __construct(){
		parent::__construct();
		$this->load->service('Match_service');

	}

	public function lol(){
		$this->Match_service->walk_lol_db();
	}



	public function trim_film_names(){
		$page = 0;
		$limit = 100;
		$this->load->model('Film_name_model');
		while($page < 100){
			echo $page . PHP_EOL;
			$names = $this->Film_name_model->f1($page++ * $limit, $limit);
			if(empty($names)){
				break;
			}

			$insert_names = array();
			foreach($names as $name){
				$tmp = trim($name['name']);
				if(!empty($tmp)){
					$insert_names[] = array('film_id' => $name['film_id'], 'name' => $tmp);
				}
				$name['name'] = trim($name['name']);
			}

			$this->Film_name_model->insert_batch($insert_names);
		}
	}

	/************************************************* private methods *************************************************************/


}