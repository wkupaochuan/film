<?php
class Film_bt_model extends CI_Model {
	function __construct()
	{
		parent::__construct();
		$this->load->database();
	}

	function insert($bt)
	{
		$this->db->insert('film_bts', $bt);
	}

	function insert_batch($bt)
	{
		$this->db->insert_batch('film_bts', $bt);
	}

}