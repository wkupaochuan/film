<?php

class Film_match extends MY_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->service('Film_service');
	}

	public function hot(){
		$films = $this->Film_service->get_hot_and_un_match_films();
		$this->assign('data', array(
			'films' => $films
		));

		$this->display('cms/hot_un_match.tpl');
	}
}
