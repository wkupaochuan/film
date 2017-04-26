<?php
class Film_recom_model extends MY_Model {
	private $_table = 'film_recom';
	function __construct()
	{
		parent::__construct();
	}

	function insert_batch($recoms)
	{
		$this->_insert_ignore_batch($this->_table, $recoms);
	}

	/**
	 * 根据相关推荐获取还未爬取的豆瓣id
	 * @param $offset
	 * @param $limit
	 * @return mixed
	 */
	function get_un_crawed_douban_ids($offset, $limit){
		$sql = <<<SQL
			select douban_id from (
				select DISTINCT(recom_douban_id) as douban_id from film_recom where invalid_times < 1
			) as a where a.douban_id NOT IN (
				select douban_id from film
			) limit {$offset}, {$limit};
SQL;

		$query = $this->_get_db()->query($sql);
		return $query->result_array();
	}

	/**
	 * 更新不可爬取次数
	 * @param $recom_douban_id
	 */
	function incr_invalid_times($recom_douban_id ){
		$recom_douban_id = intval($recom_douban_id);
		$sql = "UPDATE film_recom SET invalid_times = invalid_times + 1 where recom_douban_id = {$recom_douban_id}";
		$this->_get_db()->query($sql);
	}
}