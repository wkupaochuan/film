<?php
class Genre_model extends MY_Model {
	protected $_table = 'genre_dic';
	function __construct()
	{
		parent::__construct();
	}

	public function get_all_genre(){
		$sql = "select * from {$this->_table}";
		return $this->_c_query($sql);
	}
}