<?php
class Un_douban_model extends MY_Model {
	protected $_table = 'un_douban';
	function __construct()
	{
		parent::__construct();
	}

	function insert_batch($data)
	{
		$this->_insert_ignore_batch($this->_table, $data);
	}

	public function get($offset, $limit){
		$sql = "select * from un_douban where `tag` = 1 limit {$offset},{$limit}";
		return $this->_c_query($sql);
	}

	public function mark($douban_id){
		$sql = 'UPDATE un_douban SET tag = 2 where douban_id = ' . intval($douban_id);
		$this->_exe_write_sql($sql);
	}
}