<?php if (!defined('BASEPATH')) exit('No direct access allowed.');
class MY_Controller extends CI_Controller {
	public function __construct() {
		parent::__construct();
		$this->_load_data();
	}

	public function assign($key, $val) {
		$this->cismarty->assign($key, $val);
	}

	public function display($html) {
		$this->cismarty->assign('SEND_TONGJI', $this->_send_tongji());
		$this->cismarty->assign('PIC_HOST', $this->config->item('PIC_HOST'));
		$this->cismarty->assign('ENV', ENVIRONMENT);
		$this->cismarty->assign('content_html',$html);
		$this->cismarty->display('base/base.tpl');
	}

	protected function _param($key){
		return !empty($this->input->get($key))? $this->input->get($key):$this->input->post($key);
	}

	/**************************** prvate methods ********************************************/
	private function _load_data(){
		// 加载电影类型
		$this->load->Model('Genre_model');
		$db_genre_dic = $this->Genre_model->get_all_genre();
		$genre_dic = array();
		foreach($db_genre_dic as $tmp){
			$genre_dic[$tmp['desc']] = $tmp;
		}
		$this->config->set_item('film_genre_dic', $genre_dic);
	}

	private function _send_tongji(){
		$send = 0;
		if(ENVIRONMENT == 'production'){
			$uri = strtolower($_SERVER['REQUEST_URI']);
			if(strpos($uri, '&debug=1') === false && strpos($uri, '/cms/') === false){
				$send = 1;
			}
		}

		return $send;
	}
}