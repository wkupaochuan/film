<?php
class Lol_bt_batch_model extends MY_Model {
	protected $_table = 'lol_bts_batch';
	function __construct()
	{
		parent::__construct();
	}

	function insert($bt)
	{
		$this->_get_db()->insert($this->_table, $bt);
		return $this->_get_db()->insert_id();
	}

}