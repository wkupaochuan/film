<?php if (!defined('BASEPATH')) exit('No direct access allowed.');
class MY_Controller extends CI_Controller {
	public function __construct() {
		parent::__construct();
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
}