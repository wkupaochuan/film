<?php
class Test_s extends MY_Controller {

	public function test(){
		$page = 0;
		$limit = 20;
		$this->load->model('Film_model');
		$this->load->model('Film_name_model');
		$this->load->model('Genre_model');

		$db_genre_dic = $this->Genre_model->get_all_genre();
		$genre_dic = array();
		foreach($db_genre_dic as $tmp){
			$genre_dic[$tmp['desc']] = $tmp;
		}
		while($page < 100000){
			$films = $this->Film_model->get($page++ * $limit, $limit);
			if(empty($films)){
				echo 'end ' . $page . PHP_EOL;
				break;
			}

			echo $page . PHP_EOL;
			$urls = array();
			foreach($films as $tmp){
				if($tmp['download_able'] == 1){
					$urls[] = 'http://dyzyweb.com/film/detail?id=' . $tmp['id'];
				}
			}

			if(!empty($urls)){
				$this->_push($urls);
			}
		}
	}


	private function _push($urls){
		$api = 'http://data.zz.baidu.com/urls?site=dyzyweb.com&token=xZsWGsvBUAvYMbNd';
		$ch = curl_init();
		$options =  array(
			CURLOPT_URL => $api,
			CURLOPT_POST => true,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_POSTFIELDS => implode("\n", $urls),
			CURLOPT_HTTPHEADER => array('Content-Type: text/plain'),
		);
		curl_setopt_array($ch, $options);
		$result = curl_exec($ch);
		echo $result;
	}
}
