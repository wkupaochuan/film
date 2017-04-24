<?php
class Film_pic_model extends MY_Model {
	function __construct()
	{
		parent::__construct();
	}

	function insert($pic)
	{
		$this->_get_db()->insert('film_pic', $pic);
	}

	public function get_pics($offset, $limit)
	{
		$offset = intval($offset);
		$limit = intval($limit);
		$sql = "select * from film_pic limit where file_name IS NULL or  file_name = '' {$offset},{$limit}";
		$query = $this->_get_db()->query($sql);
		return $query->result_array();
	}

	public function update_file_name($id, $file_name)
	{
		$this->_get_db()->reconnect();
		$id = intval($id);
		$file_name = $this->_get_db()->escape($file_name);
		$sql = "UPDATE `film_pic` SET `file_name` = {$file_name} where `id` = {$id} ";
		$this->_get_db()->query($sql);
	}

	/**
	 * @param $douban_id
	 */
	public function get_pics_by_douban_id($douban_id)
	{
		$douban_id = intval($douban_id);
		$sql = "select file_name from film_pic where douban_id = {$douban_id} AND file_name IS NOT NULL;";
		$query = $this->_get_db()->query($sql);
		return $query->result_array();
	}

	public function get_urls_by_douban_id($douban_id)
	{
		$sql = "select DISTINCT(douban_url) from film_pic where douban_id = {$douban_id} ";
		$query = $this->_get_db()->query($sql);
		return $query->result_array();
	}

	public function delete_not_empty_url_id($count)
	{
		$sql = "select * from (select douban_id, douban_url, count(1) as cc from film_pic GROUP BY douban_id, douban_url) as a where cc > 1 ORDER BY cc desc,douban_id desc limit {$count};";
		$query = $this->_get_db()->query($sql);
		foreach ($query->result_array() as $tmp){
			$sql = "select id from film_pic where douban_id = {$tmp['douban_id']} and douban_url = '{$tmp['douban_url']}' ORDER by file_name DESC";
			$query = $this->_get_db()->query($sql);
			$res = $query->result_array();
			if(!empty($res) && count($res) > 1) {
				$sql = "DELETE from film_pic where douban_id = {$tmp['douban_id']} and douban_url = '{$tmp['douban_url']}' AND id !=  {$res[0]['id']};";
				$this->_get_db()->query($sql);
			}
		}
	}

}