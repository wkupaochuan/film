<?php
class Film_recom_service extends MY_Service{

    /**************************************public methods****************************************************************************/

	/**
	 *
	 * @param $film_id
	 * @param $recom_ids
	 */
	public function process_douban_recom($film_id, $recom_ids){
		$insert_data = array();
		$recom_douban_ids = array_unique($recom_ids);
		foreach($recom_douban_ids as $tmp){
			array_push($insert_data, array(
				'film_id' => $film_id,
				'recom_douban_id' => $tmp,
			));
		}

		if(!empty($insert_data)){
			$this->load->model('Film_recom_model');
			$this->Film_recom_model->insert_batch($insert_data);
		}
	}

    /**************************************private methods****************************************************************************/


} 