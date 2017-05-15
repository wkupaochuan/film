<?php
class Douban_comments_model extends MY_Model {
	protected $_table = 'douban_comments';
	function __construct()
	{
		parent::__construct();
	}

	public function insert_batch($data){
		$this->_get_db()->insert_batch($this->_table, $data);
	}

	public function query_by_douban_id_and_people($douban_id, $people_ids){
		$douban_id = intval($douban_id);
		if(empty($douban_id) || empty($people_ids)){
			return;
		}
		$arr = array();
		foreach($people_ids as $tmp){
			$arr[] = "'" . $this->_get_db()->escape_str($tmp) . "'";
		}
		$p = implode(',', $arr);

		$sql = "select people_id from douban_comments where douban_id = {$douban_id} and people_id in ({$p});";
		return $this->_c_query($sql);
	}

	public function get_film_comments_count($douban_id){
		$douban_id = intval($douban_id);
		if(empty($douban_id)){
			return false;
		}

		$sql = "select count(1) as c_c from douban_comments where douban_id = {$douban_id}";
		$result =  $this->_c_query($sql);
		return $result[0]['c_c'];
	}
}