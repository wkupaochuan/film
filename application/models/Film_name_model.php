<?php
class Film_name_model extends CI_Model {
	private $_table = 'film_names';

	function __construct()
	{
		parent::__construct();
		$this->load->database();
	}

	function insert_batch($names)
	{
		$this->db->insert_batch($this->_table, $names);
	}

	function search_by_name($name) {
		$sql = "select * from film_names where `name` like '%" . $this->db->escape_str($name) ."%';";
		$query = $this->db->query($sql);
		return $query->result_array();
	}

}