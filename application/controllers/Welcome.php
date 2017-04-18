<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Welcome extends MY_Controller {

	public function index()
	{
	}

	public function import_data($data_path)
	{
		$data_path = str_replace(':', '/', $data_path);
		$fp = fopen($data_path, 'r');
		$this->load->model('Film_model');

		$i = 0;
		while(!feof($fp)) {
			if($i > 100000000) {
				break;
			}

			$i++;

			$line = trim(fgets($fp));
			if(empty($line)) {
				continue;
			}

			$data = json_decode($line, true);
			if(empty($data) || !is_array($data)) {
				echo '空:' . PHP_EOL . $line . PHP_EOL;
			}

//			print_r($data);

			$or_data = $this->Film_model->get_by_douban_id($data['id']);
			if(empty($or_data)) {
				$this->Film_model->insert(array(
					'douban_id' => $data['id'],
					'ch_name' => !empty($data['ch_name'])? $data['ch_name']:'',
					'or_name' => !empty($data['or_name'])? $data['or_name']:'',
					'other_names' => !empty($data['other_names'])? json_encode($data['other_names']):'',
					'year' => !empty($data['year'])? $data['year']:'',
					'director' => !empty($data['director'])? $data['director']:'',
					'actors' => !empty($data['actors'])? implode(',', $data['actors']):'',
					'genre' => !empty($data['genre'])? implode(',', $data['genre']):'',
					'runtime' => !empty($data['runtime'])? $data['runtime']:'',
					'douban_rate' => !empty($data['rate'])? $data['rate']:'',
					'summary' => !empty($data['summary'])? $data['summary']:'',
					'related_pics' => !empty($data['related_pics'])? json_encode($data['related_pics']):'',
					'comments' => !empty($data['comments'])? json_encode($data['comments']):'',
					'douban_link' => !empty($data['link'])? $data['link']:'',
					'douban_post_cover' => !empty($data['post_cover'])? $data['post_cover']:'',
				));
			}
		}

		fclose($fp);
	}


}
