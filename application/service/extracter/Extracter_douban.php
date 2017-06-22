<?php

class Extracter_douban extends MY_Service{

	public function __construct(){
		parent::__construct();

	}

	public function process($douban_id){
		$ret = array();

		if(empty($douban_id)){
			return $ret;
		}

		return $ret;
	}

	/**************************************private methods****************************************************************************/

	private function _extract_detail($douban_id){

	}

}