<?php
class Lol_film_model extends MY_Model {
	protected $_table = 'lol_film';
	function __construct()
	{
		parent::__construct();
	}

	public function get_film_detail_by_url($url){
		if(empty($url)){
			return;
		}

		$sql = "select * from " . $this->_table . " where `url`=" . $this->_get_db()->escape($url);
		return $this->_c_query_unique($sql);
	}

	public function update_film_by_id($id, $data){
		$where = array(
			'id' => $id
		);
		$this->_get_db()->update($this->_table, $data, $where);
	}

	public function get_film_by_range($offset, $limit){
		$sql = "select * from {$this->_table} limit {$offset},{$limit}";
		return $this->_c_query($sql);
	}
}