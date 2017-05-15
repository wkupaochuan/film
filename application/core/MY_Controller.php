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
		$this->cismarty->assign('PIC_HOST', $this->config->item('PIC_HOST'));
		$this->cismarty->assign('ENV', ENVIRONMENT);
		$this->cismarty->assign('content_html',$html);
		$this->cismarty->display('base/base.tpl');
	}

	protected function _from_spider(){
		$ua = !empty($_SERVER['HTTP_USER_AGENT'])? strtolower($_SERVER['HTTP_USER_AGENT']):'';
		return strpos($ua, 'spider') !== false;
	}

	protected function _c_echo($str)  {
		echo $str . PHP_EOL;
	}

	protected function _log_error($msg, $function = 0, $line = 0){
		$this->_c_echo('user error :on function ' . $function . ' line ' . $line . ':' . $msg);
	}

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
}