<?php

class Film_ac_log_model extends MY_Model{
	protected $_table = 'film_ac_log';

	public function __construct(){
		parent::__construct();
	}

	/**
	 * 记录访问次数, 不存在insert, 存在update
	 * @param $film_id
	 * @param $day
	 * @param $times
	 * @return bool|mixed
	 */
	public function ac($film_id, $day, $times){
		if(empty($film_id) || empty($day) || empty($times)){
			return false;
		}

		$film_id = intval($film_id);
		$day = strtotime(date('Y-m-d', $day));
		$times = intval($times);

		$sql = <<<SQL
			INSERT INTO {$this->_table} (film_id,`day`,`ac_times`) VALUES ({$film_id}, {$day}, {$times})
			ON DUPLICATE KEY UPDATE ac_times=ac_times+{$times}
SQL;

		return $this->_exe_write_sql($sql);
	}

	/**
	 * 获取热门电影
	 * @param $begin_time
	 * @param $end_time
	 * @param int $limit
	 * @return mixed
	 */
	public function get_hot_films($begin_time, $end_time, $limit = 20){
		$sql = <<<SQL
			select * from (
			select film_id, SUM(ac_times) as ac_times from film_ac_log where `day` >= {$begin_time} and `day` <= {$end_time}  GROUP BY film_id) as a
			order by a.ac_times desc limit 0,{$limit}
SQL;

		return $this->_c_query($sql);
	}


}