<?php
class MY_Model extends CI_Model {
	protected $_table = '';
	public function __construct() {
		parent::__construct();
		$this->load->database();
	}

	/**
	 * 新增
	 * @param $data
	 * @return bool
	 */
	public function insert($data){
		if(empty($this->_table) || empty($data)){
			return false;
		}
		$this->_get_db()->insert($this->_table, $data);
		return $this->_get_db()->insert_id();
	}

	protected function _get_db(){
		static $last_query_time;
		if(empty($last_query_time)){
			$last_query_time = time();
		}else{
			if((time() - $last_query_time) > 3){
				if(!$this->db->reconnect()){
					$this->load->database();
				}
			}
		}
		return $this->db;
	}

	protected function _insert_ignore_batch($table, $insert_data){
		if(empty($table) || empty($insert_data)){
			return 0;
		}

		$column_sql = '(' . implode(',', array_keys($insert_data[0])) . ')';
		$values_sql_item_array = array();
		foreach($insert_data as $item){
			$v = array();
			foreach($item as $tmp){
				array_push($v, $this->_get_db()->escape($tmp));
			}
			$values_sql_item_array[] = '(' . implode(',', $v) . ')';
		}
		$value_sql = implode(',', $values_sql_item_array);
		$sql = "insert IGNORE INTO {$table} {$column_sql} VALUES $value_sql ;";

		$this->_get_db()->query($sql);

		return $this->_get_db()->affected_rows();
	}

	protected function _c_query($sql){
		$query = $this->_get_db()->query($sql);
		return $query->result_array();
	}

	protected function _c_query_unique($sql){
		$query_res = $this->_c_query($sql);
		return empty($query_res)? array():$query_res[0];
	}
}