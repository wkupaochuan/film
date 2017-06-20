<?php
class Test_t_model extends MY_Model {
	protected $_table = 'test_ms';
	function __construct()
	{
		parent::__construct();
	}

	public function insert_batch($ids){
		if(empty($ids)){
			return false;
		}

		$insert_data = array();
		foreach($ids as $tmp_id){
			$insert_data[] = array(
				'item_id' => intval($tmp_id)
			);
		}

		return $this->_insert_batch($insert_data);
	}

	public function search_by_item($item_id){
		$item_id = intval($item_id);

		if(empty($item_id)){
			return false;
		}

		$sql = "select * from {$this->_table} where item_id = {$item_id}";
		return $this->_c_query($sql);
	}
}