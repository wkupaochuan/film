<?php
class MY_Model extends CI_Model {
	public function __construct() {
		parent::__construct();
		$this->load->database();
	}

	/**
	 * 重连
	 */
	protected function _ping(){
		if(!$this->db->reconnect()){
			$this->load->database();
		}
	}
}