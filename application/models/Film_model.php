<?php
class Film_model extends MY_Model {
	private $_table = 'film';
	function __construct()
	{
		parent::__construct();
	}

	public function query_by_name($search_words)
	{
		$search_words = $this->_get_db()->escape_str($search_words);
		$query = $this->_get_db()->query("select * from film where `ch_name` like '%{$search_words}%' ");
		return $query->result_array();
	}

	function insert($film)
	{
		$this->_get_db()->insert('film', $film);
		return $this->_get_db()->affected_rows();
	}

	function get_by_douban_ids($douban_ids)
	{
		if(empty($douban_ids)) {
			return array();
		}
		foreach($douban_ids as &$id) {
			$id = intval($id);
		}
		$query = $this->_get_db()->query("select * from film where douban_id in ( " . implode(',', $douban_ids) . ")");
		return $query->result_array();
	}

	function get_by_douban_id($douban_id)
	{
		$douban_id = intval($douban_id);
		$query = $this->_get_db()->query("select * from film where douban_id = " . $douban_id);
		$result = $query->result_array();
		return empty($result)? array():$result[0];
	}

	function get_detail_by_id($id)
	{
		$id = intval($id);
		$sql = "select * from `film` where `id` = " . $id;
		$query = $this->_get_db()->query($sql);
		$result = $query->result_array();
		return empty($result)? array():$result[0];
	}

	public function update_recom_ids($ids, $douban_id) {
		$ids = $this->_get_db()->escape($ids);
		$douban_id = intval($douban_id);
		$sql = "UPDATE film SET recom_douban_id = {$ids} where douban_id = {$douban_id} ";
		$this->_get_db()->query($sql);
	}

	public function get($offset, $limit)
	{
		$sql = "select * from film limit {$offset},{$limit}";
		$query = $this->_get_db()->query($sql);
		return $query->result_array();
	}

	public function update_loldytt_info($id, $info)
	{
		$update_info = array(
			'download_able' => 1,
			'loldytt_url' => $info['loldytt_url'],
			'loldytt_genre' => $info['loldytt_cat']
		);
		$where = array(
			'id' => $id
		);

		$this->_get_db()->update($this->_table, $update_info, $where);
	}

	public function update_douban_post_cover($id, $post_cover){
		$update_info = array(
			'douban_post_cover' => $post_cover,
		);
		$where = array(
			'id' => $id
		);

		$this->_get_db()->update($this->_table, $update_info, $where);
	}

	public function query_by_name_and_actors($name, $actors)
	{
		$search_words = $this->_get_db()->escape_str($name);
		$actors = $this->_get_db()->escape_str($actors);
		$query = $this->_get_db()->query("select * from film where `ch_name` like '%{$search_words}%' and actors like '%{$actors}%' ");
		return $query->result_array();
	}

	public function query_by_actors_and_douban_id($douban_ids, $actor) {
		$sql = "select * from film where douban_id in (". implode(',', $douban_ids) . ") and actors like '%" . $this->_get_db()->escape_str($actor) . "%' ";

		$query = $this->_get_db()->query($sql);
		return $query->result_array();
	}

	public function query_by_director_and_douban_id($douban_ids, $director) {
		$sql = "select * from film where douban_id in (". implode(',', $douban_ids) . ") and  director like '" . $this->_get_db()->escape_str($director) . "%';";

		$query = $this->_get_db()->query($sql);
		return $query->result_array();
	}

	public function query_by_year_and_douban_id($douban_ids, $year) {
		$sql = "select * from film where douban_id in (". implode(',', $douban_ids) . ") and  `year`=" . intval($year);

		$query = $this->_get_db()->query($sql);
		return $query->result_array();
	}

	public function update_by_douban_id($douban_id, $update_info){
		if(empty($douban_id) || empty($update_info)){
			return;
		}
		$where = array(
			'douban_id' => $douban_id
		);
		$this->_get_db()->update($this->_table, $update_info, $where);
	}

	public function get_recom_films($douban_id){
		$douban_id = intval($douban_id);
		$sql = <<<SQL
			select * from film where douban_id IN (
				select recom_douban_id from film_recom where douban_id = {$douban_id}
			);
SQL;

		$query = $this->_get_db()->query($sql);
		return $query->result_array();
	}

	public function query_by_lol_url($url){
		$url = $this->_get_db()->escape($url);
		$sql = "select * from {$this->_table} where `lol_url` = {$url}";
		return $this->_c_query_unique($sql);
	}
}