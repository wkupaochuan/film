<?php
class Film_name_model extends MY_Model {
	protected $_table = 'film_names';

	function __construct()
	{
		parent::__construct();
	}

	function insert_batch($names)
	{
		$this->_insert_ignore_batch($this->_table, $names);
	}

	function search_by_name($name) {
		$sql = "select * from film_names where `name` like '%" . $this->_get_db()->escape_str($name) ."%';";
		$query = $this->_get_db()->query($sql);
		return $query->result_array();
	}

	function update_by_douban_id($douban_id, $film_id){
		$sql = "UPDATE film_names SET film_id ={$film_id} where douban_id ={$douban_id} ";
		return $this->_get_db()->query($sql);
	}

	function search_by_names($names) {
		$sql = "select * from film_names ";

		$like_arr = array();
//		foreach($names as $name){
//			$like_arr[] = " `name` like '%" . $this->_get_db()->escape_str($name) ."%'";
//		}

		foreach($names as $name){
			$like_arr[] = " `name` = '" . $this->_get_db()->escape_str($name) ."'";
		}

		$like = " where " . implode(' or ', $like_arr);
		$sql .= $like;
//		echo $sql;exit;
		$query = $this->_get_db()->query($sql);
		return $query->result_array();
	}

    function get_by_film_id($film_id){
        $film_id = intval($film_id);
        $sql = "select * from {$this->_table} WHERE film_id = {$film_id}";
        return $this->_c_query($sql);
    }

    function delete_by_ids($ids){
        if(empty($ids)){
            return;
        }
        $in_sql = implode(',' , $ids);
        $sql = "delete from {$this->_table} where id in ({$in_sql})";
        $this->_get_db()->query($sql);
    }

	public function f1($offset, $limit){
		$sql = "select * from film_names_copy limit {$offset},{$limit}";
		return $this->_c_query($sql);
	}

}