<?php
class Film_pic_service extends MY_Service{

    /**************************************public methods****************************************************************************/

	/**
	 * 处理图片
	 * @param $film_id
	 * @param $pics
	 */
	public function add_film_pics($film_id, $pics){
		$this->load->model('Film_pic_model');
		$exist_file_names = $this->Film_pic_model->get_by_film_id($film_id);
		$exist_file_names = array_column($exist_file_names, 'file_name');

		foreach($pics as $pic_url){
			$pic_file_name = substr($pic_url, strrpos($pic_url, '/') + 1);
			if(!empty($pic_url) && !in_array($pic_file_name, $exist_file_names)){
				$this->_down_and_store_pic($film_id, $pic_url);
			}
		}
	}

	/**
	 * 更新封面(下载、上传、更新db)
	 * @param $film_id
	 * @param $douban_post_cover_link
	 * @return bool|void
	 */
	public function update_post_cover($film_id, $douban_post_cover_link)
	{
		if(empty($film_id) || empty($douban_post_cover_link)){
			return false;
		}

		$this->load->model('Film_model');
		$or_film_detail = $this->Film_model->get_detail_by_id($film_id);
		// 已经存在的不再更新 todo
		if(!empty($or_film_detail['b_post_cover']) && $or_film_detail['b_post_cover'] != 'movie_default_large'){
			return true;
		}

		$down_pic_url = str_replace('https', 'http', $douban_post_cover_link);
		$down_pic_file_name = substr($douban_post_cover_link, strrpos($douban_post_cover_link, '/') + 1);

		$b_down_pic_url = $l_down_pic_url  = '';
		if(strpos($down_pic_url, 'ipst') !== false || strpos($down_pic_url, 'lpst') !== false ){
			$b_down_pic_url = str_replace('ipst', 'lpst', $down_pic_url);
			$l_down_pic_url = str_replace('lpst', 'ipst', $down_pic_url);
		}else if(strpos($down_pic_url, 'lpic') !== false || strpos($down_pic_url, 'spic') !== false){
			$b_down_pic_url = str_replace('spic', 'lpic', $down_pic_url);
			$l_down_pic_url = str_replace('lpic', 'spic', $down_pic_url);
		}else if(strpos($down_pic_url, '_default_') !== false){
			$update_info = array('b_post_cover' => 'movie_default_large.png');
			$this->Film_model->update_by_id($film_id, $update_info);
			$update_info = array('l_post_cover' => 'movie_default_small.png');
			$this->Film_model->update_by_id($film_id, $update_info);
			return true;
		}

		if(!empty($b_down_pic_url)){
			$pending_down_content = array(
				array(
					'type' => 1,
					'url' => $b_down_pic_url,
					'file_name' => 'pcl_' . $down_pic_file_name,
				),
				array(
					'type' => 2,
					'url' => $l_down_pic_url,
					'file_name' => 'pci_' . $down_pic_file_name,
				)
			);

			foreach($pending_down_content as $tmp){
				$down_pic_url = $tmp['url'];
				if($this->_download_and_upload($down_pic_url, $tmp['file_name'])){
					$update_info = $tmp['type'] == 1?  array('b_post_cover' => $tmp['file_name'],):array('l_post_cover' => $tmp['file_name'],);
					$this->Film_model->update_by_id($film_id, $update_info);
				}
			}

			return true;
		}else{
			return false;
		}
	}

    /**************************************private methods****************************************************************************/

	/**
	 * 下载并上传、存储图片
	 * @param $film_id
	 * @param $pic_url
	 */
	private function _down_and_store_pic($film_id, $pic_url){
		if(empty($film_id) || empty($pic_url)){
			return;
		}

		$this->load->model('Film_pic_model');
		$pic_url = str_replace('https', 'http', $pic_url);
		$down_pic_file_name = substr($pic_url, strrpos($pic_url, '/') + 1);

		if($this->_download_and_upload($pic_url, $down_pic_file_name)){
			$insert_data = array(
				'film_id' => $film_id,
				'file_name' => $down_pic_file_name,
			);
			$this->Film_pic_model->insert($insert_data);
		}
	}

	/**
	 * 下载并上传
	 * @param $url
	 * @param $file_name
	 * @return bool
	 */
	private function _download_and_upload($url, $file_name){
		if(empty($url) || empty($file_name)){
			return false;
		}

		$ret = false;

		$down_pic_file_full_path = '/tmp/' . $file_name;
		$cmd = "wget -q {$url} -O $down_pic_file_full_path";
		exec($cmd);
		if(file_exists($down_pic_file_full_path) && filesize($down_pic_file_full_path) > 10) {
			// 上传
			if($this->qiniu->upload($down_pic_file_full_path, $file_name)){
				$ret = true;
			}

		}
		@unlink($down_pic_file_full_path);

		return $ret;
	}

} 