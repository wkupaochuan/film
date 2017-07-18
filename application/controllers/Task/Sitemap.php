<?php
class Sitemap extends MY_Controller {

	public function up(){
		$fp = fopen(FCPATH . 'sitemap_updated_index.xml', 'w+');
		$stime = strtotime(date('Y-m-d', time()));
		$this->load->service('Film_service');
		$up_film_ids = $this->Film_service->get_up_films($stime);
		$this->_write_sitemap_header($fp);
		if(!empty($up_film_ids)){
			foreach($up_film_ids as $film){
				$this->_write_sitemap_item($fp, array(
					'url' => 'http://dyf1024.com/film/detail?id=' . $film['id'],
					'time' => $stime,
				));
			}
		}
		$this->_write_sitemap_item($fp, array(
			'url' => 'http://dyf1024.com',
			'time' => $stime,
		));
		$this->_write_sitemap_footer($fp);
		fclose($fp);
	}

	public function full(){
		$fp = fopen(FCPATH . 'sitemap_index.xml', 'w+');
		$page = 0;
		$limit = 50;
		$this->load->model('Film_model');
		$this->_write_sitemap_header($fp);
		while($page < 10000){
			$films = $this->Film_model->get_download_able_films($page++ * $limit, $limit);

			if(empty($films)){
				break;
			}

			foreach($films as $film){
				$this->_write_sitemap_item($fp, array(
					'url' => 'http://dyf1024.com/film/detail?id=' . $film['id'],
					'time' => $film['up_time'],
				));
			}
		}
		$this->_write_sitemap_footer($fp);
		fclose($fp);
	}

	/************************************************* private methods *************************************************************/

	private function _write_sitemap_header($fp){
		fputs($fp, '<?xml version="1.0" encoding="utf-8"?>' . PHP_EOL);
		fputs($fp, '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL);
	}

	private function _write_sitemap_footer($fp){
		fputs($fp, '</urlset>');
	}

	private function _write_sitemap_item($fp, $item){
		fputs($fp, '	<url>' . PHP_EOL);
		fputs($fp, "		<loc>" . $item['url'] . "</loc>"  . PHP_EOL);
		fputs($fp, '		<lastmod>' . date('Y-m-d', $item['time']) .'</lastmod>'  . PHP_EOL);
		fputs($fp, '	</url>'  . PHP_EOL);
	}
}
