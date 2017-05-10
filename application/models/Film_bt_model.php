<?php
class Film_bt_model extends MY_Model {
	protected $_table = 'film_bts';
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

	function get_by_film_id($film_id){
		$sql = "select bt.id, bt.batch_id, bt.`name`, bt.url, batch.type from film_bts bt left join film_bts_batch batch on bt.batch_id = batch.id where bt.film_id = " . intval($film_id);
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

	function update_by_douban_id($douban_id, $film_id){
		$sql = "UPDATE film_bts SET film_id ={$film_id} where douban_id ={$douban_id} ";
		return $this->_get_db()->query($sql);
	}

	/**
	 * 根据时间区间获取bt
	 * @param $timestamp
	 * @return bool
	 */
	public function query_by_time($timestamp){
		$timestamp = intval($timestamp);
		if(empty($timestamp)){
			return false;
		}

		$sql = "select DISTINCT(film_id) from film_bts where ctime >= FROM_UNIXTIME({$timestamp})";
		return $this->_c_query($sql);
	}
}