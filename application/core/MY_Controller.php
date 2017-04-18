<?php if (!defined('BASEPATH')) exit('No direct access allowed.');
class MY_Controller extends CI_Controller {
	public function __construct() {
		parent::__construct();
	}

	public function assign($key, $val) {
		$this->cismarty->assign($key, $val);
	}

	public function display($html) {
		$this->cismarty->assign('content_html',$html);
		$this->cismarty->display('base/base.tpl');
	}
}