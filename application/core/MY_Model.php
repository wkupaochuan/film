<?php
class MY_Model extends CI_Model {
	public function __construct() {
		parent::__construct();
		$this->load->database();
	}

	protected function _get_db(){
		static $last_query_time;
		if(empty($last_query_time)){
			$last_query_time = time();
		}else{
			if((time() - $last_query_time) > 3){
				$this->db->reconnect();
			}
		}
		return $this->db;
	}
}