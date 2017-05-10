<?php
class Film_service extends MY_Service{

    /**************************************public methods****************************************************************************/

	/**
	 * 处理名称
	 * @param $douban_id
	 * @param $names
	 */
	public function process_film_names($film_id, $names){
		$insert_names = array();
		foreach($names as $name){
			array_push($insert_names, array(
				'name' => $name,
				'film_id' => $film_id,
			));
		}
		$this->load->model('Film_name_model');
		$this->Film_name_model->insert_batch($insert_names);
	}

    /**************************************private methods****************************************************************************/


} 