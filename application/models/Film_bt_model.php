<?php
class Film_bt_model extends MY_Model {
	private $_table = 'film_bts';
	function __construct()
	{
		parent::__construct();
	}

	function insert($bt)
	{
		$this->db->insert('film_bts', $bt);
	}

	function insert_batch($bt)
	{
		$this->db->insert_batch('film_bts', $bt);
	}

	function get_by_douban_id($douban_id){
		$sql = "select * from " . $this->_table . " where douban_id = " . intval($douban_id);
		$query = $this->db->query($sql);
		return $query->result_array();
	}

}