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

	public function get_un_matched_films($offset, $limit, $un_match_times_limit = 0){
		$sql = "select * from {$this->_table} where film_id = 0 and un_match_times <= {$un_match_times_limit} limit {$offset},{$limit}";
		return $this->_c_query($sql);
	}

	public function incr_un_match_times($lol_film_id){
		$lol_film_id = intval($lol_film_id);
		$sql = "update lol_film SET un_match_times = un_match_times + 1 where id = {$lol_film_id}";
		return $this->_get_db()->query($sql);
	}
}