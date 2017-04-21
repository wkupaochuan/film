<?php
class Film_model extends MY_Model {
	private $_table = 'film';
	function __construct()
	{
		parent::__construct();
	}

	public function query_by_name($search_words)
	{
		$search_words = $this->db->escape_str($search_words);
		$query = $this->db->query("select * from film where `ch_name` like '%{$search_words}%' ");
		return $query->result_array();
	}

	function insert($film)
	{
		$this->db->insert('film', $film);
		return $this->db->affected_rows();
	}

	function get_by_douban_ids($douban_ids)
	{
		if(empty($douban_ids)) {
			return array();
		}
		foreach($douban_ids as &$id) {
			$id = intval($id);
		}
		$query = $this->db->query("select * from film where douban_id in ( " . implode(',', $douban_ids) . ")");
		return $query->result_array();
	}

	function get_by_douban_id($douban_id)
	{
		$douban_id = intval($douban_id);
		$query = $this->db->query("select * from film where douban_id = " . $douban_id);
		$result = $query->result_array();
		return empty($result)? array():$result[0];
	}

	function get_detail_by_id($id)
	{
		$id = intval($id);
		$sql = "select * from `film` where `id` = " . $id;
		$query = $this->db->query($sql);
		$result = $query->result_array();
		return empty($result)? array():$result[0];
	}

	public function update_recom_ids($ids, $douban_id) {
		$ids = $this->db->escape($ids);
		$douban_id = intval($douban_id);
		$sql = "UPDATE film SET recom_douban_id = {$ids} where douban_id = {$douban_id} ";
		$this->db->query($sql);
	}

	public function get($offset, $limit)
	{
		$sql = "select * from film limit {$offset},{$limit}";
		$query = $this->db->query($sql);
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

		$this->db->update($this->_table, $update_info, $where);
	}

	public function update_douban_post_cover($id, $post_cover){
		$update_info = array(
			'douban_post_cover' => $post_cover,
		);
		$where = array(
			'id' => $id
		);

		$this->db->update($this->_table, $update_info, $where);
	}

	public function query_by_name_and_actors($name, $actors)
	{
		$search_words = $this->db->escape_str($name);
		$actors = $this->db->escape_str($actors);
		$query = $this->db->query("select * from film where `ch_name` like '%{$search_words}%' and actors like '%{$actors}%' ");
		return $query->result_array();
	}

	public function query_by_actors_and_douban_id($douban_ids, $actor) {
		$sql = "select * from film where douban_id in (". implode(',', $douban_ids) . ") and actors like '%" . $this->db->escape_str($actor) . "%';";
		$query = $this->db->query($sql);
		return $query->result_array();
	}

	public function update_by_douban_id($douban_id, $update_info){
		$where = array(
			'douban_id' => $douban_id
		);
		$this->db->update($this->_table, $update_info, $where);
	}

	public function get_recom_films($douban_id){
		$douban_id = intval($douban_id);
		$sql = <<<SQL
			select * from film where douban_id IN (
				select recom_douban_id from film_recom where douban_id = {$douban_id}
			);
SQL;

		$query = $this->db->query($sql);
		return $query->result_array();
	}
}