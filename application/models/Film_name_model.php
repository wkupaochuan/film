<?php
class Film_name_model extends MY_Model {
	protected $_table = 'film_names';

	function __construct()
	{
		parent::__construct();
	}

	function insert_batch($names)
	{
		$this->_insert_ignore_batch($this->_table, $names);
	}

	function search_by_name($name) {
		$sql = "select * from film_names where `name` like '%" . $this->_get_db()->escape_str($name) ."%';";
		$query = $this->_get_db()->query($sql);
		return $query->result_array();
	}

	function update_by_douban_id($douban_id, $film_id){
		$sql = "UPDATE film_names SET film_id ={$film_id} where douban_id ={$douban_id} ";
		return $this->_get_db()->query($sql);
	}

}