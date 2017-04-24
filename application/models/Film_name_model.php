<?php
class Film_name_model extends MY_Model {
	private $_table = 'film_names';

	function __construct()
	{
		parent::__construct();
	}

	function insert_batch($names)
	{
		$this->_get_db()->insert_batch($this->_table, $names);
	}

	function search_by_name($name) {
		$sql = "select * from film_names where `name` like '%" . $this->_get_db()->escape_str($name) ."%';";
		$query = $this->_get_db()->query($sql);
		return $query->result_array();
	}

}