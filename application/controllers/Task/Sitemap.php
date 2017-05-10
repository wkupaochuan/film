<?php
class Sitemap extends MY_Controller {

	/**
	 * 生成每日更新的sitemap
	 */
	public function gen_daily_site_map(){
		$stime = strtotime(date('Y-m-d', time() - 86400));
		$this->load->model('Film_bt_model');
		$up_film_ids = $this->Film_bt_model->query_by_time($stime);
		if(!empty($up_film_ids)){
			$urls = array();
			foreach($up_film_ids as $film_id){
				$urls[] = array(
					'url' => 'http://dyzyweb.com/film/detail?id=' . $film_id['film_id'],
					'time' => $stime,
				);
			}

			$this->_gen_up_sitemap($urls);
		}
	}

	/************************************************* private methods *************************************************************/

	/**
	 * 生成sitemap
	 * @param $urls
	 * @return bool
	 */
	private function _gen_up_sitemap($urls){
		if(empty($urls)){
			return false;
		}

		$this->_c_echo('<?xml version="1.0" encoding="utf-8"?>');
		$this->_c_echo('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">');

		foreach($urls as $tmp){
			$this->_c_echo('	<url>');
			$this->_c_echo("		<loc>" . $tmp['url'] . "</loc>");
			$this->_c_echo('		<lastmod>' . date('Y-m-d', $tmp['time']) .'</lastmod>');
			$this->_c_echo('	</url>');
		}

		$this->_c_echo('</urlset>');

	}
}
