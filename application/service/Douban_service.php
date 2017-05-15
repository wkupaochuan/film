<?php
class Douban_service extends MY_Service{

    /**************************************public methods****************************************************************************/

	/**
	 * 爬取评论
	 * @param $douban_id
	 * @return bool
	 */
	public function craw_comments($douban_id){
		$douban_id = intval($douban_id);
		if(empty($douban_id)){
			return false;
		}

		$this->load->model('Douban_comments_model');
		$film_c_count = $this->Douban_comments_model->get_film_comments_count($douban_id);
		if(intval($film_c_count) > 10){
			return true;
		}

		$url_pattern = 'https://movie.douban.com/subject/' . $douban_id . '/comments?start=%s&limit=%s&sort=time&status=P';
		$start = 0;
		$limit = 20;
		while(true){
			f_echo("comments on douban {$douban_id}, start on {$start}");
			if($start > 1000){
				break;
			}
			$url = sprintf($url_pattern, $start, $limit);
			$html = $this->_get_comments_html($url);

			// 提取评论内容
			$comments = $this->_extract_comments($html);
			if(!empty($comments)){
				$this->_add_comments($douban_id, $comments);
			}

			// 获取下一页url
			$next = $this->_check_end_for_comment($html);
			if($next['end']){
				f_echo('end page on craw comment');
				break;
			}

			if(empty($next['start']) || empty($next['limit'])){
				f_log_error('get no next info on ' . $start . $limit);
				break;
			}

			$start = $next['start'];
			$limit = $next['limit'];

//			$e_time = $comments[count($comments) - 1]['time'];
		}
	}


    /**
     * 登录
     * @param string $cp
     * @param string $cp_id
     */
    public function login($cp = '', $cp_id = ''){
        $url = 'https://accounts.douban.com/login';
        $data = array(
            'form_email' => 'a1qifa@126.com',
            'form_password' => 'a1qifa+000000',
            'login' => '登录',
        );

        if(!empty($cp)){
            $data = array(
                'captcha-solution' => $cp,
                'captcha-id' => $cp_id,
            );
        }

        // <img id="captcha_image" src="https://www.douban.com/misc/captcha?id=KyDbTWqR6MGwIIfuCPta1xGx:en&amp;size=s" alt="captcha" class="captcha_image"/>
        $res = $this->_request_douban($url, $data);
        $pattern = '#<img id="captcha_image" src="([\s\S]*)" alt="captcha" class="captcha_image"/>#U';
        $matches = array();
        preg_match($pattern, $res, $matches);
        if(!empty($matches) && !empty($matches[1])){
            $this->_c_echo('需要验证码:' . $matches[1]);
        }else{
            $this->_c_echo('登录成功');
        }
    }

    /**************************************private methods****************************************************************************/

	/**
	 * 新增评论, 带唯一性检查
	 * todo 检查是否有更新(根据时间检查即可)
	 * @param $douban_id
	 * @param $comments
	 */
	private function _add_comments($douban_id, $comments){
		if(empty($comments) || empty($douban_id)){
			return;
		}

		$people_ids = array_column($comments, 'people_id');
		$this->load->model('Douban_comments_model');
		$db_commets = $this->Douban_comments_model->query_by_douban_id_and_people($douban_id, $people_ids);
		$db_people_ids = array_column($db_commets, 'people_id');

		$insert_data = array();
		foreach($comments as $tmp_comment){
			if(!in_array($tmp_comment['people_id'], $db_people_ids)){
				$tmp_comment['douban_id'] = $douban_id;
				empty($tmp_comment['avatar_url']) && $tmp_comment['avatar_url'] = '';
				empty($tmp_comment['people_id']) && $tmp_comment['people_id'] = '';
				empty($tmp_comment['people_name']) && $tmp_comment['people_name'] = '';
				empty($tmp_comment['rate']) && $tmp_comment['rate'] = '';
				empty($tmp_comment['time']) && $tmp_comment['time'] = '';
				empty($tmp_comment['content']) && $tmp_comment['content'] = '';
				empty($tmp_comment['vote']) && $tmp_comment['vote'] = '';
				$insert_data[] = $tmp_comment;
			}
		}

		if(!empty($insert_data)){
			$this->Douban_comments_model->insert_batch($insert_data);
		}
	}

	/**
	 * 校验是否没有更多评论了
	 * @param $html
	 * @return bool
	 */
	private function _check_end_for_comment($html){
		$ret = array(
			'end' => false,
			'start' => 0,
			'limit' => 0,
		);

		if(empty($html)){
			$ret['end'] = true;
			return $ret;
		}

		$pattern = '#<div id="paginator" class="center">([\s\S]*)</div>#U';
		$matches = array();
		preg_match($pattern, $html, $matches);
		if(!empty($matches) && !empty($matches[1])){
			$block_htlm = $matches[1];
			$pattern = '#<a ([\s\S]*)>([\s\S]*)</a>#U';
			$matches = array();
			preg_match_all($pattern, $block_htlm, $matches);

			if(!empty($matches) && !empty($matches[1])){
				$end_html = '';
				if(count($matches[1]) == 1){
					// 首页
					$end_html = $matches[1][0];
				}else if(count($matches[1]) == 3){
					// 普通页
					$end_html = $matches[1][2];
				}else if(count($matches[1]) == 2){
					// 尾页
					$ret['end'] = true;
				}

				if(!empty($end_html)){
					$pattern = '#start=([\d]+)&amp;limit=([\d]+)&amp;#U';
					$matches = array();
					preg_match($pattern, $end_html, $matches);
					if(!empty($matches) && !empty($matches[1]) && !empty($matches[2])){
						$ret['start'] = $matches[1];
						$ret['limit'] = $matches[2];
					}
				}
			}
		}

		return $ret;
	}

	/**
	 * 获取评论页面html, 带重试3次
	 * @param $url
	 * @return bool
	 */
	private function _get_comments_html($url){
		if(empty($url)){
			return '';
		}
		$retry = 3;
		$html = '';
		while($retry-- >= 0){
			$html = $this->_request_douban($url);
			$pattern = 'class="next">后页';
			if(strpos($html, $pattern) !== false){
				break;
			}else{
				$html = '';
			}
		}

		if(empty($html)){
			f_log_error('get nothing on douban comments url:' . $url);
		}

		return $html;
	}

	/**
	 * 提取评论内容
	 * @param $html
	 * @return array array('avatar_url', 'people_id', 'people_name', 'rate', 'time', 'content', 'vote' );
	 */
	private function _extract_comments($html){
		$comments = array();

		empty($html) && $html = '';
		$pattern = '#<div class="comment-item" data-cid="([\d]+)">([\s\S]*)</p>#U';
		$matches = array();
		preg_match_all($pattern, $html, $matches);
		if(!empty($matches) && !empty($matches[2])){
			foreach($matches[0] as $block_html){
				$tmp_com = array();

				// avatar url
				$pattern = '#<img src="([\s\S]*)" class="" />#U';
				$matches = array();
				preg_match($pattern, $block_html, $matches);
				if(!empty($matches) && !empty($matches[1])){
					$tmp_com['avatar_url'] = $matches[1];
				}

				// people
				$pattern = '#<a href="https://www.douban.com/people/([\s\S]*)/" class="">([\s\S]*)</a>#U';
				$matches = array();
				preg_match($pattern, $block_html, $matches);
				if(!empty($matches)){
					if(!empty($matches[1])){
						$tmp_com['people_id'] = $matches[1];
					}
					if(!empty($matches[2])){
						$tmp_com['people_name'] = $matches[2];
					}
				}

				// rate 0-5 star
				$pattern = '#<span class="allstar([\d]{2}) rating"#U';
				$matches = array();
				preg_match($pattern, $block_html, $matches);
				if(!empty($matches) && !empty($matches[1])){
					$tmp_com['rate'] = intval($matches[1]);
				}

				// time
				$pattern = '#<span class="comment-time " title="([\s\S]*)">#U';
				$matches = array();
				preg_match($pattern, $block_html, $matches);
				if(!empty($matches) && !empty($matches[1])){
					$tmp_com['time'] = strtotime($matches[1]);
				}

				// content
				$pattern = '#<p class="">([\s\S]*)</p>#U';
				$matches = array();
				preg_match($pattern, $block_html, $matches);
				if(!empty($matches) && !empty($matches[1])){
					$tmp_com['content'] = trim($matches[1]);
				}

				// vote
				$pattern = '#<span class="votes">([\d]+)</span>#U';
				$matches = array();
				preg_match($pattern, $block_html, $matches);
				if(!empty($matches) && !empty($matches[1])){
					$tmp_com['vote'] = intval($matches[1]);
				}

				if(!empty($tmp_com)){
					$comments[] = $tmp_com;
				}
			}
		}

		return $comments;
	}

	/**
	 * 采用登陆后的cookie来请求
	 * @param $url
	 * @param array $post_data
	 * @return mixed|string
	 */
	private function _request_douban($url, $post_data = array()){
		sleep(rand(3,20));

		$header = array(
			'Accept' => '*/*',
			'Accept-Encoding' => 'gzip, deflate',
			'Accept-Language' => 'zh-CN,zh;q=0.8',
			'Connection' => 'keep-alive',
			'Referer' => 'https => //accounts.douban.com/login?alias=*******略',
			'User-Agent' => 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/45.0.2454.101 Safari/537.36',
		);
		$cookie_file_path = '/tmp/douban_login_cookie.txt';

		return f_curl($url, $post_data, $cookie_file_path, $header);
	}


}