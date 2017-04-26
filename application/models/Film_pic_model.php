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
}