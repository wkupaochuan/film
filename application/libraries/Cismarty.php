<?php
if(!defined('BASEPATH')) EXIT('No direct script asscess allowed');
require_once( APPPATH . 'third_party/smarty/Smarty.class.php' );

class Cismarty extends Smarty {
	protected $ci;
	public function  __construct(){
		$this->ci = & get_instance();
		$this->ci->load->config('smarty');//加载smarty的配置文件

		if(is_mobile()){
			$all_configs = $this->ci->config->item('smarty_h5');
		}else{
			$all_configs = $this->ci->config->item('smarty_pc');
		}

		//获取相关的配置项
		$this->template_dir = $all_configs['template_dir'];
		$this->compile_dir = $all_configs['compile_dir'];
		$this->cache_dir = $all_configs['cache_dir'];
		$this->config_dir = $all_configs['config_dir'];
//		$this->ext = $all_configs('template_ext');
//		$this->caching = $all_configs('caching');
	}
}