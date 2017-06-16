<?php

class Match extends  MY_Controller{
	public function __construct(){
		parent::__construct();
		$this->load->service('Match_service');

	}

	public function manual($douban_id, $lol_url){

	}

	public function lol(){
		$this->Match_service->walk_lol_db();
	}

	public function manual_lol($douban_id, $url){
		$url = str_replace(':', '/', $url);
		$this->Match_service->lol_manual($douban_id, $url);
	}

	public function me($path){
		$path = str_replace(':', '/', $path);
		$fp = fopen($path, 'r');
		while($line = fgets($fp)){
			$line = trim($line);
			if(empty($line)){
				break;
			}

			$params = explode('/', $line);
			if(count($params) != 4){
				f_log_error('error' . $line);
			}
			$bt = $params[0];
			$type = $params[1];
			$film_id = $params[2];
			$name = $params[3];

			echo $bt . PHP_EOL;
			echo $type . PHP_EOL;
			echo $film_id . PHP_EOL;
			echo $name . PHP_EOL;
//			exit;
			$this->Match_service->me($bt, $type, $film_id, $name);
		}
		fclose($fp);

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