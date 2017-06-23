<?php
class Film_model extends MY_Model {
	protected $_table = 'film';
	function __construct()
	{
		parent::__construct();
	}

	/**
	 * 用户搜索专用
	 * @param $search_words
	 * @return mixed
	 */
	public function query_by_name_for_user_search($search_words)
	{
		$search_words = $this->_escape_str($search_words);
		$sql = <<<SQL
			select * from film WHERE id IN (
				select DISTINCT(film_id) from film_names where `name` like '%{$search_words}%'
			) AND download_able = 1 limit 0,20;
SQL;

		return $this->_c_query($sql);
	}

	function get_by_douban_ids($douban_ids)
	{
		if(empty($douban_ids)) {
			return array();
		}
		foreach($douban_ids as &$id) {
			$id = intval($id);
		}
		$sql = "select * from film where douban_id in ( " . implode(',', $douban_ids) . ")";
		return $this->_c_query($sql);
	}

	function get_by_ids($film_ids, $attrs = array())
	{
		if(empty($film_ids)) {
			return array();
		}
		foreach($film_ids as &$id) {
			$id = intval($id);
		}

        if(empty($attrs)){
            $sql = "select * from film where id in ( " . implode(',', $film_ids) . ")";
        }else{
            $attr_sql = implode(',', $attrs);
            $sql = "select {$attr_sql} from film where id in ( " . implode(',', $film_ids) . ")";
        }


		return $this->_c_query($sql);
	}

	function get_by_douban_id($douban_id)
	{
		$douban_id = intval($douban_id);
		return $this->_c_query_unique("select * from film where douban_id = " . $douban_id);
	}

	function get_detail_by_id($id)
	{
		$id = intval($id);
		$sql = "select * from `film` where `id` = " . $id;
		return $this->_c_query_unique($sql);
	}

	public function get($offset, $limit, $attrs = array())
	{
        if(empty($attrs)){
            $sql = "select * from film limit {$offset},{$limit}";
        }else{
            $attr_sql = implode(',', $attrs);
            $sql = "select {$attr_sql} from film limit {$offset},{$limit}";
        }

		return $this->_c_query($sql);
	}

	public function get_download_able_films($offset, $limit)
	{
		$sql = "select id, up_time from film where download_able = 1 limit {$offset},{$limit}";
		return $this->_c_query($sql);
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

		return $this->update($update_info, $where);
	}

	public function query_by_name_and_actors($name, $actors)
	{
		$search_words = $this->_escape_str($name);
		$actors = $this->_escape_str($actors);
		return $this->_c_query("select * from film where `ch_name` like '%{$search_words}%' and actors like '%{$actors}%' ");
	}

	public function query_by_actors_and_id($film_ids, $actor) {
		$sql = "select * from film where id in (". implode(',', $film_ids) . ") and actors like '%" . $this->_escape_str($actor) . "%' ";

		return $this->_c_query($sql);
	}

	public function query_by_director_and_id($film_ids, $director) {
		$sql = "select * from film where id in (". implode(',', $film_ids) . ") and  director like '" . $this->_escape_str($director) . "%';";

		return $this->_c_query($sql);
	}

	public function query_by_year_and_id($film_ids, $year) {
		$sql = "select * from film where id in (". implode(',', $film_ids) . ") and  `year`=" . intval($year);

		return $this->_c_query($sql);
	}

	public function update_by_douban_id($douban_id, $update_info){
		if(empty($douban_id) || empty($update_info)){
			return false;
		}
		$where = array(
			'douban_id' => intval($douban_id)
		);

		return $this->update($update_info, $where);
	}

	public function update_by_id($id, $update_info){
		if(empty($id) || empty($update_info)){
			return false;
		}
		$where = array(
			'id' => intval($id)
		);

		return $this->update($update_info, $where);
	}

	public function get_recom_films($film_id){
		$film_id = intval($film_id);
		$sql = <<<SQL
			select * from film where douban_id IN (
				select recom_douban_id from film_recom where film_id = {$film_id}
			);
SQL;

		return $this->_c_query($sql);
	}

	public function query_by_lol_url($url){
		$url = $this->_escape($url);
		$sql = "select * from {$this->_table} where `lol_url` = {$url}";
		return $this->_c_query_unique($sql);
	}

	public function query_by_genre($genre, $offset, $limit)
	{
		$genre = intval($genre);
		if(empty($genre) || $genre < 2){
			$sql = "select * from `film` where download_able = 1 order by `year` desc limit {$offset},{$limit}";
		}else{
			$sql = "select * from `film` where  download_able = 1 and `genre_p`%{$genre}=0 order by `year` desc limit {$offset},{$limit}";
		}

		return $this->_c_query($sql);
	}

	public function query_by_up_time($timestamp){
		$timestamp = intval($timestamp);
		$sql = "select * from {$this->_table} where download_able = 1 and up_time >= {$timestamp}";
		return $this->_c_query($sql);
	}
}