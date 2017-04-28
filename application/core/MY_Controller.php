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

	protected function _curl($url, $post_data = array(), $cookie_jar = '', $header = array()){
		$proxy = array();
		$proxy_array = array(
			array(
				'ip' => '111.13.7.119',
				'port' => '8080',
			),
			array(
				'ip' => '111.13.2.131',
				'port' => '80',
			),
		);
		if(($rand = rand(0, count($proxy_array))) < count($proxy_array)){
			$proxy = $proxy_array[$rand];
		}

		$ch = curl_init();
		if(!empty($proxy)){
			curl_setopt($ch,CURLOPT_PROXY, $proxy['ip']);
			curl_setopt($ch,CURLOPT_PROXYPORT, $proxy['port']);
		}
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 5);
		if(!empty($post_data)) {
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
		}
		if(!empty($cookie_jar)) {
			curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_jar);
			curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_jar);
		}
		if(!empty($header)){
			curl_setopt ($ch, CURLOPT_HTTPHEADER, $header);
		}

		$retry_times = 3;
		while($retry_times > 0){
			$retry_times--;
			$res  = curl_exec($ch);
			$errno     = curl_errno($ch);
			$http_code = @curl_getinfo($ch, CURLINFO_HTTP_CODE);
			if($errno == 0 && strlen($res) > 300){
				break;
			}
		}
		curl_close($ch);
//		echo $http_code . PHP_EOL. 'errno:' . $errno . PHP_EOL . $errmsg . PHP_EOL . $res;exit;
		if(strlen($res) < 300 || $errno != 0) {
			$errmsg    = (0 != $errno) ? curl_error($ch) : '';
			$this->_log_error('curl fail.http code:' . $http_code .';errno:' .  $errno . ';errmsg:' . $errmsg . ';url:' . $url);
			return '';
		}else{
			return $res;
		}
	}

	/**
	 * 处理名称
	 * @param $douban_id
	 * @param $names
	 */
	protected function _process_film_names($douban_id, $names){
		$insert_names = array();
		foreach($names as $name){
			array_push($insert_names, array(
				'name' => $name,
				'douban_id' => $douban_id,
			));
		}
		$this->load->model('Film_name_model');
		$this->Film_name_model->insert_batch($insert_names);
	}
}