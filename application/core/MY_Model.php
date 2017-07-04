<?php
class MY_Model extends CI_Model {
	const DB_TYPE_MASTER = 'master';
	const DB_TYPE_SLAVE = 'slave';

	protected $_table = '';
	private static $slave_last_query_time = 0;
	private static $master_last_query_time = 0;
	private static $master_db = null;

	public function __construct() {
		parent::__construct();
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
		$this->_get_db(self::DB_TYPE_MASTER)->insert($this->_table, $data);
		return $this->_get_db(self::DB_TYPE_MASTER)->insert_id();
	}

	/**
	 * 更新
	 * @param $update_info
	 * @param $where
	 * @return mixed
	 */
	public function update($update_info, $where){
		$this->_get_db(self::DB_TYPE_MASTER)->update($this->_table, $update_info, $where);
		return $this->_affected_rows();
	}

	/**
	 * 获取db
	 * @param string $db_type
	 * @return mixed
	 */
	protected function _get_db($db_type = self::DB_TYPE_SLAVE){
		if($db_type == self::DB_TYPE_MASTER){
			if(empty(self::$master_last_query_time)){
				self::$master_db = $this->load->database('master', true);
				self::$master_last_query_time = time();
			}else if((time() - self::$master_last_query_time) > 3){
				if(!self::$master_db->reconnect()){
					self::$master_db =$this->load->database('master', true);
					self::$master_last_query_time = time();
				}
			}
		}else{
			if(empty(self::$slave_last_query_time)){
				$this->load->database();
				self::$slave_last_query_time = time();
			}else if((time() - self::$slave_last_query_time) > 3){
				if(!$this->db->reconnect()){
					$this->load->database();
					self::$slave_last_query_time = time();
				}
			}
		}

		return $db_type == self::DB_TYPE_MASTER? self::$master_db:$this->db;
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
				array_push($v, $this->_get_db(self::DB_TYPE_MASTER)->escape($tmp));
			}
			$values_sql_item_array[] = '(' . implode(',', $v) . ')';
		}
		$value_sql = implode(',', $values_sql_item_array);
		$sql = "insert IGNORE INTO {$table} {$column_sql} VALUES $value_sql ;";

		$this->_get_db(self::DB_TYPE_MASTER)->query($sql);

		return $this->_affected_rows();
	}

	protected function _c_query($sql){
		$query = $this->_get_db()->query($sql);
		return $query->result_array();
	}

	protected function _c_query_unique($sql){
		$query_res = $this->_c_query($sql);
		return empty($query_res)? array():$query_res[0];
	}

	protected function _insert_batch($datas){
		$this->_get_db(self::DB_TYPE_MASTER)->insert_batch($this->_table, $datas);
		return $this->_affected_rows();
	}

	/**
	 * 执行写语句
	 * @param $sql
	 * @return mixed
	 */
	protected function _exe_write_sql($sql){
		$this->_get_db(self::DB_TYPE_MASTER)->query($sql);
		return $this->_affected_rows();
	}

	/**
	 * 转义字符串
	 * @param $str
	 * @return mixed
	 */
	protected function _escape_str($str){
		return $this->_get_db(self::DB_TYPE_SLAVE)->escape_str($str);
	}

	/**
	 * 转义字符串
	 * @param $str
	 * @return mixed
	 */
	protected function _escape($str){
		return $this->_get_db(self::DB_TYPE_SLAVE)->escape($str);
	}

	/**************************************private methods****************************************************************************/

	private function _affected_rows(){
		return $this->_get_db(self::DB_TYPE_MASTER)->affected_rows();
	}
}