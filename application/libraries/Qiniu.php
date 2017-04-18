<?php
require_once APPPATH . '/third_party/qiniu/autoload.php';
// 引入鉴权类
use Qiniu\Auth;
// 引入上传类
use Qiniu\Storage\UploadManager;

class Qiniu {
	// 需要填写你的 Access Key 和 Secret Key
	private $accessKey = '1ChkZST1lYEAPRKPLQcEXHnIYeKjUWhx6nZbC1nD';
	private $secretKey = 'Vi3g-lK-pw49PzsXchOkoJ-_-Ll6iBP8uE8boKA_';

	public function upload($file_path, $name) {
		if(empty($file_path) || !is_file($file_path) || !file_exists($file_path)) {
			return false;
		}

		// 构建鉴权对象
		$auth = new Auth($this->accessKey, $this->secretKey);
		// 要上传的空间
		$bucket = 'test';
		// 生成上传 Token
		$token = $auth->uploadToken($bucket);
		// 上传到七牛后保存的文件名
		// 初始化 UploadManager 对象并进行文件的上传。
		$uploadMgr = new UploadManager();
		// 调用 UploadManager 的 putFile 方法进行文件的上传。
		list($ret, $err) = $uploadMgr->putFile($token, $name, $file_path);
		return empty($err);
	}

}