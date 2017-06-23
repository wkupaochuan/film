<?php
class Film_pic_model extends MY_Model {
	protected $_table = 'film_pic';
	function __construct()
	{
		parent::__construct();
	}

	/**
	 * @param $film_id
	 */
	public function get_by_film_id($film_id)
	{
		$film_id = intval($film_id);
		$sql = "select file_name from film_pic where film_id = {$film_id} AND file_name IS NOT NULL;";
		return $this->_c_query($sql);
	}

	function update_by_douban_id($douban_id, $film_id){
		$sql = "UPDATE film_pic SET film_id ={$film_id} where douban_id ={$douban_id} ";
		return $this->_exe_write_sql($sql);
	}
}