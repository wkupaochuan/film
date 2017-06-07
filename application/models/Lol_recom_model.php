<?php
class Lol_recom_model extends MY_Model {
	protected $_table = 'lol_recom';
	function __construct()
	{
		parent::__construct();
	}

	function insert_batch($recoms)
	{
		$this->_insert_ignore_batch($this->_table, $recoms);
	}

	/**
	 * 更新不可爬取次数
	 * @param $recom_url
	 */
	function incr_invalid_times($recom_url){
		if(empty($recom_url)){
			return;
		}
		$recom_url = $this->_get_db()->escape($recom_url);
		$sql = "UPDATE {$this->_table} SET invalid_times = invalid_times + 1 where recom_url = {$recom_url}";
		$this->_get_db()->query($sql);
	}

	/**
	 * 根据相关推荐获取还未爬取的url
	 * @param $offset
	 * @param $limit
	 * @return mixed
	 */
	function get_un_crawed_urls($offset, $limit){
		$sql = <<<SQL
			select DISTINCT(lol_url) from (
				select recom_url as lol_url from lol_recom where invalid_times < 5
			) as a where a.lol_url NOT IN (
				select url  from lol_film where url IS NOT NULL
			) limit {$offset}, {$limit};
SQL;

		$query = $this->_get_db()->query($sql);
		return $query->result_array();
	}
}