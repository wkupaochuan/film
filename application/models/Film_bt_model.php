<?php
class Film_bt_model extends MY_Model {
	private $_table = 'film_bts';
	function __construct()
	{
		parent::__construct();
	}

	function insert($bt)
	{
		$this->_get_db()->insert($this->_table, $bt);
	}

	function insert_batch($bt)
	{
		$this->_get_db()->insert_batch($this->_table, $bt);
	}

	function get_by_douban_id($douban_id){
		$sql = "select * from " . $this->_table . " where douban_id = " . intval($douban_id);
		$query = $this->_get_db()->query($sql);
		return $query->result_array();
	}

	function get_by_urls($urls){
		foreach($urls as &$url){
			$url = $this->_get_db()->escape($url);
		}
		$sql = "select * from " . $this->_table . " where `url` in (" . implode(',', $urls) . ")";
		return $this->_c_query($sql);
	}

}