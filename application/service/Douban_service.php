<?php
class Douban_service extends MY_Service{

    public function __construct(){
        parent::__construct();
        $this->load->model('Douban_comments_model');
        $this->load->model('Film_model');
        $this->load->model('Douban_comments_model');
        $this->load->model('Genre_model');
        $this->load->model('Film_name_model');
        $this->load->service('Film_service');
        $this->load->service('Film_pic_service');
        $this->load->service('Film_recom_service');
        $this->load->service('Film_recom_service');
	    $this->load->service('extracter/Extracter_douban');
    }

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
            f_echo('需要验证码:' . $matches[1]);
        }else{
	        f_echo('登录成功');
        }
    }

    /**
     * 爬取并存储豆瓣电影(爬取、入库、处理图片、处理海报)
     * @param $douban_id
     * @return bool
     */
    public function craw_and_store_douban_film($douban_id){
        $db_film_detail = $this->Film_model->get_by_douban_id($douban_id);

        // 爬取
        $douban_film_detail = $this->Extracter_douban->process($douban_id, array(), true);
        if(empty($douban_film_detail)){
            f_log_error('craw get nothing from ' . $douban_id);
            return false;
        }

        // insert film
        $insert_film_data = array(
            'douban_id' => $douban_film_detail['id'],
            'ch_name' => !empty($douban_film_detail['ch_name'])? $douban_film_detail['ch_name']:'',
            'or_name' => !empty($douban_film_detail['or_name'])? $douban_film_detail['or_name']:'',
            'year' => !empty($douban_film_detail['year'])? $douban_film_detail['year']:'',
            'director' => !empty($douban_film_detail['director'])? implode('/', $douban_film_detail['director']):'',
            'actors' => !empty($douban_film_detail['actors'])? implode(',', $douban_film_detail['actors']):'',
            'genre' => !empty($douban_film_detail['genre'])? implode(',', $douban_film_detail['genre']):'',
            'genre_p' => !empty($douban_film_detail['genre'])? $this->_cal_genre_product($douban_film_detail['genre']):1,
            'runtime' => !empty($douban_film_detail['runtime'])? $douban_film_detail['runtime']:'',
            'douban_rate' => !empty($douban_film_detail['rate'])? $douban_film_detail['rate']:'',
            'summary' => !empty($douban_film_detail['summary'])? $douban_film_detail['summary']:'',
//            'comments' => !empty($douban_film_detail['comments'])? json_encode($douban_film_detail['comments']):'',
            'recom_douban_id' => !empty($douban_film_detail['recomm_ids'])? implode(',', $douban_film_detail['recomm_ids']):'',
        );

        $film_id = null;
        if(empty($db_film_detail)){
            $film_id = $this->Film_model->insert($insert_film_data);
            if(empty($film_id)){
                f_log_error('insert db fail ' . $douban_id);
                return false;
            }
        }else{
            $film_id = $db_film_detail['id'];
            $this->Film_model->update_by_douban_id($douban_id, $insert_film_data);
        }

        if(empty($film_id)){
            return false;
        }

        // process names
        $insert_names = !empty($douban_film_detail['other_names'])? $douban_film_detail['other_names']:array();
        $this->Film_service->process_film_names($film_id, $insert_names);

        // handle pic
        if(!empty($douban_film_detail['related_pics'])){
            $this->Film_pic_service->add_film_pics($film_id,  $douban_film_detail['related_pics']);
        }

        // handle post cover
        if(!empty($douban_film_detail['post_cover'])){
            $this->Film_pic_service->update_post_cover($film_id, $douban_film_detail['post_cover']);
        }

        // handle recom
        if(!empty($douban_film_detail['recomm_ids'])){
            $this->Film_recom_service->process_douban_recom($film_id, $douban_film_detail['recomm_ids']);
        }

        // 标记已抓取
        $this->Film_recom_service->up_un_douban($douban_id);

        return true;
    }

	/**
	 * 重写电影名称
	 * @param int $start
	 */
    public function overwrite_names($start = 0){
        $fail = $success = $nil = $done = 0;
        $limit = 50;
	    $page = $start/$limit;

	    $this->load->model('Test_t_model');

        while($page < 10000){
            f_echo('page:' . $page);
            $films = $this->Film_model->get($page++ * $limit, $limit, array('id', 'douban_id', 'ch_name', 'or_name'));
            if(empty($films)){
                break;
            }

	        $success_ids = array();
            foreach($films as $db_film_detail){
                $done_ids = $this->Test_t_model->search_by_item($db_film_detail['id']);
	            if(!empty($done_ids)){
		            f_echo("has been done : " . $db_film_detail['id']);
		            $done++;
		            continue;
	            }

	            // 抓取
                $crawed_film_detail = $douban_film_detail = $this->Extracter_douban->process($db_film_detail['douban_id'], array('ch_name', 'or_name', 'other_names'));
                if(empty($crawed_film_detail)){
                    f_log_error("fail on craw:" . $db_film_detail['douban_id'] . ", " . $db_film_detail['ch_name']);
	                $fail++;
                    continue;
                }

	            // 更新film表
	            $up_film_table = 0;
	            if($db_film_detail['ch_name'] != $crawed_film_detail['ch_name'] || $db_film_detail['or_name'] != $crawed_film_detail['or_name']){
		            f_echo("update film table " . $db_film_detail['id']);
		            $this->Film_model->update_by_id($db_film_detail['id'], array(
			            'ch_name' => $crawed_film_detail['ch_name'],
			            'or_name' => $crawed_film_detail['or_name'],
		            ));
		            $up_film_table = 1;
	            }

                $db_film_names = $this->Film_name_model->get_by_film_id($db_film_detail['id']);
                $in_db_err_names = $new_names = $intersection = array();
                if(!empty($crawed_film_detail['other_names'])){
                    foreach($db_film_names as $db_film_name_row){
                        if(!in_array($db_film_name_row['name'], $crawed_film_detail['other_names'])){
                            $in_db_err_names[] = $db_film_name_row['id'];
                        }else{
                            $intersection[] = $db_film_name_row['name'];
                        }
                    }

	                $new_names = array_diff($crawed_film_detail['other_names'], $intersection);
	                !empty($new_names) && $new_names = array_values($new_names);
                    foreach($new_names as $index => $tmp){
	                    $new_names[$index] = array(
                            'film_id' => $db_film_detail['id'],
                            'name' => $tmp,
                        );
                    }
                }

	            if(!empty($new_names)){
		            $this->Film_name_model->insert_batch($new_names);
		            f_echo("new names " . $db_film_detail['id'] . ':' . implode('/', array_column($new_names, 'name')));
	            }
	            if(!empty($in_db_err_names)){
		            $this->Film_name_model->delete_by_ids($in_db_err_names);
		            f_echo("delete names " . $db_film_detail['id'] . ':' . implode('/', $in_db_err_names));
	            }

	            if(empty($new_names) && empty($in_db_err_names) && empty($up_film_table)){
		            f_echo('nothing to update:' . $db_film_detail['id']);
		            $nil++;
	            }else{
		            f_echo('update success:' . $db_film_detail['id']);
		            $success++;
		            // 防止主从不同步
		            if($success%3 == 0) {sleep(2);}
	            }
	            $success_ids[] = $db_film_detail['id'];
            }

	        $this->Test_t_model->insert_batch($success_ids);
        }

	    f_echo('overwrite names end:' . $nil . '-' . $success . '-' . $fail. '-' . $done);
    }

	/**
	 * 获取更新的条目
	 * @param $url
	 * @return array
	 */
	public function craw_updated_items($url){
		$douban_ids = array();
		$res_str = $this->_request_douban($url);
		if(!empty($res_str)){
			$items = json_decode($res_str, true);
			if(!empty($items) && !empty($items['subjects'])){
				foreach($items['subjects'] as $item){
					$douban_ids[] = $item['id'];
				}
			}
		}

		return $douban_ids;
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
		sleep(rand(1,3));

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

    /**
     * 计算类型乘积
     * @param $genre_desc_arr
     * @return int
     */
    private function _cal_genre_product($genre_desc_arr){
        $genre_p = 1;
        if(empty($genre_desc_arr)){
            return $genre_p;
        }

        $genre_dic = $this->config->item('film_genre_dic');
        foreach($genre_desc_arr as $genre_desc){
            if(empty($genre_dic[$genre_desc])){
                $xx = end($genre_dic);
                $g_dic = array(
                    'genre_id' => empty($genre_dic)? get_closest_prime(0):get_closest_prime($xx['genre_id']),
                    'desc' => $genre_desc,
                );
                $this->Genre_model->insert($g_dic);
                $genre_dic[$genre_desc] = $g_dic;
            }
            $genre_p *= $genre_dic[$genre_desc]['genre_id'];
        }

        $this->config->set_item('film_genre_dic', $genre_dic);

        return $genre_p;
    }
}