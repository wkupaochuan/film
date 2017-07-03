<?php
require_once(APPPATH . 'service/extracter/Extracter_base.php');

class Extracter_douban extends Extracter_base{

	public function __construct(){
		parent::__construct();

		$this->load->service('parser/Parser_douban');
	}

	/**
	 * @param $douban_id
	 * @param array $attrs
	 * @param bool $from_remote 是否强制从远端拉取
	 * @return array
	 */
	public function process($douban_id, $attrs = array(), $from_remote = false){
		$ret = array();

		if(empty($douban_id)){
			return $ret;
		}

		// 从远端拉取
		if($from_remote || !$this->Parser_douban->exist($douban_id)){
			$this->Parser_douban->process($douban_id);
		}

		if(!$this->Parser_douban->exist($douban_id)){
			return $ret;
		}

		// 提取
		$ret = $this->_extract_detail($douban_id, $attrs);

		return $ret;
	}

	/**************************************private methods****************************************************************************/

	/**
	 * 提取
	 * @param $douban_id
	 * @param array $attrs
	 * @return array
	 */
	private function _extract_detail($douban_id, $attrs = array()) {
		$ret = array();
		$doc = new DOMDocument();
		@$doc->loadHTMLFile($this->Parser_douban->cal_path($douban_id));

		// 主名称、原名称、年份
		if(empty($attrs) || in_array('ch_name', $attrs)){
			$title_node = $doc->getElementsByTagName('title');
			if(!empty($title_node) && $title_node->length == 1){
				$title_node = $title_node->item(0);
				$ch_name = trim(str_replace('(豆瓣)', '', $title_node->nodeValue));
				if(!empty($ch_name)){
					$ret['ch_name'] = $ch_name;
					$ret['or_name'] = '';

					$h1_node = $doc->getElementsByTagName('h1');
					if(!empty($h1_node) && $h1_node->length == 1){
						$span_nodes = get_child_nodes_by_tag($h1_node->item(0), 'span');
						if(count($span_nodes) === 2){
							// 年份
							if(empty($attrs) || in_array('year', $attrs)){
								$ret['year'] = $span_nodes[1]->nodeValue;
							}

							$compact_name = trim($span_nodes[0]->nodeValue);
							if(strlen($ch_name) !== strlen($compact_name)){
								// 原名
								$ret['or_name'] = trim(substr($compact_name, strlen($ch_name) + 1));
							}
						}
					}
				}
			}

			if(empty($ret['ch_name'])) {
				f_log_error('no ch_name on ' . $douban_id);
			}
		}

		// 海报
		if(empty($attrs) || in_array('post_cover', $attrs)){
			$main_pic_div_node = $doc->getElementById('mainpic');
			!empty($main_pic_div_node) && $main_pic_div_node = $main_pic_div_node->getElementsByTagName('img');
			if(!empty($main_pic_div_node) && $main_pic_div_node->length == 1){
				$post_cover_url = trim($main_pic_div_node->item(0)->getAttribute('src'));
				if(!empty($post_cover_url)){
					$ret['post_cover'] = trim($post_cover_url);
				}
			}

			if(empty($ret['post_cover'])) {
				f_log_error('no post_cover ' . $douban_id);
			}
		}

		$info_div_node = $doc->getElementById('info');
		$info_html = '';
		!empty($info_div_node) && $info_html = $info_div_node->C14N();

		// 导演
		if(empty($attrs) || in_array('director', $attrs)){
			$pattern = '#rel="v:directedBy">([\s\S]*)</a>#U';
			$matches = array();
			preg_match_all($pattern, $info_html, $matches);
			if(!empty($matches[1])) {
				foreach($matches[1] as $tmp_director){
					$tmp_director = trim($tmp_director);
					if(!empty($tmp_director)){
						$ret['director'][] = $tmp_director;
					}
				}
			}
		}

		//  编剧
		if(empty($attrs) || in_array('writer', $attrs)){
			$pattern = '#<a href="/celebrity/([\d]+)/">([\s\S]*)</a>#U';
			$matches = array();
			preg_match_all($pattern, $info_html, $matches);
			if(!empty($matches[2])) {
				foreach($matches[2] as $tmp_director){
					$tmp_director = trim($tmp_director);
					if(!empty($tmp_director)){
						$ret['writer'][] = $tmp_director;
					}
				}
			}
		}

		// 主演
		if(empty($attrs) || in_array('actors', $attrs)){
			$pattern = '#<span class="actor">([\s\S]*)<br/>#U';
			$matches = array();
			preg_match($pattern, $info_html, $matches);
			if(!empty($matches[1])) {
				$actors_html = $matches[1];
				$pattern = '#rel="v:starring">([\s\S]*)</a>#U';
				$matches = array();
				preg_match_all($pattern, $actors_html, $matches);
				if(!empty($matches[1])) {
					foreach($matches[1] as $tmp_actor){
						$tmp_actor = trim($tmp_actor);
						!empty($tmp_actor) &&$ret['actors'][] = trim($tmp_actor);
					}
				}
			}
		}

		// 类型
		if(empty($attrs) || in_array('genre', $attrs)){
			$pattern = '#<span property="v:genre">([\s\S]*)</span>#U';
			$matches = array();
			preg_match_all($pattern, $info_html, $matches);
			if(!empty($matches[1])) {
				$ret['genre'] = $matches[1];
			}
		}

		// 其他名称
		if(empty($attrs) || in_array('other_names', $attrs)){
			$pattern = '#又名:</span>([\s\S]*)<br>#U';
			$matches = array();
			preg_match($pattern, $info_html, $matches);
			!empty($matches[1]) && $names = explode('/', $matches[1]);
			if(!empty($names)){
				foreach($names as $tmp_name){
					$tmp_name = trim($tmp_name);
					!empty($tmp_name) && $ret['other_names'][] = trim($tmp_name);
				}
			}

			!empty($ret['ch_name']) && $ret['other_names'][] = $ret['ch_name'];
			!empty($ret['or_name']) && $ret['other_names'][] = $ret['or_name'];
		}

		// 片长
		if(empty($attrs) || in_array('runtime', $attrs)){
			$pattern = '#<span property="v:runtime" content="(\d+)">#U';
			$matches = array();
			preg_match($pattern, $info_html, $matches);
			if(!empty($matches[1])) {
				$ret['runtime'] = $matches[1] . '分钟';
			}
		}

		// 评分
		$rate_div_node = get_unique_element_by_class($doc, 'div', 'rating_self clearfix');
		$rate_html = !empty($rate_div_node)? $rate_div_node->C14N():'';
		if(empty($attrs) || in_array('rate', $attrs)){
			$pattern = '#property="v:average">(\d.\d)</strong>#U';
			$matches = array();
			preg_match($pattern, $rate_html, $matches);
			if(!empty($matches[1])) {
				$ret['rate'] = $matches[1];
			}
		}

		// 简介
		if(empty($attrs) || in_array('summary', $attrs)){
			$summary_div = $doc->getElementById('link-report');
			if(!empty($summary_div)){
				$summary_span = get_child_nodes_by_tag($summary_div, 'span');
				if(count($summary_span) === 2){
					$ret['summary'] = trim($summary_span[0]->nodeValue);
				}
			}
		}

		// 相关图片
		$related_pics_div_node = $doc->getElementById('related-pic');
		$related_pics_div_html = !empty($related_pics_div_node)? $related_pics_div_node->C14N():'';
		if(empty($attrs) || in_array('related_pics', $attrs)){
			$pattern = '#src="([\s\S]*)"#U';
			$matches = array();
			preg_match_all($pattern, $related_pics_div_html, $matches);
			if(!empty($matches[1])) {
				$ret['related_pics'] = array_slice($matches[1], 1);
			}
		}

		// 获奖情况
		if(empty($attrs) || in_array('awards', $attrs)){
			$awards_div_node = get_unique_element_by_class($doc, 'div', 'mod');
			$awards_html = !empty($awards_div_node)? $awards_div_node->C14N():'';

			$pattern = '#<ul class="award">([\s\S]*)</ul>#U';
			$matches = array();
			preg_match_all($pattern, $awards_html, $matches);
			if(!empty($matches[1])) {
				$awards = array();
				foreach($matches[1] as $tmp) {
					$pattern = '#<li>([\s\S]*)</li>#U';
					$matches = array();
					preg_match_all($pattern, $tmp, $matches);
					if(count($matches[1]) === 3) {
						$award = array();
						$award['award'] = substr($matches[1][0], stripos($matches[1][0], '>'), strrpos($matches[1][0], '<') - stripos($matches[1][0], '>') );
						$award['type'] = $matches[1][1];

						$who_matches = array();
						$pattern = '#target="_blank">([\s\S]*)</a>#U';
						preg_match_all($pattern, $matches[1][2], $who_matches);
						if(!empty($who_matches[1])){
							$award['who'] = implode('/', $who_matches[1]);
						}
						if(!empty($award)) $awards[] = $award;
					}
				}
				$ret['awards'] = $awards;
			}
		}

		// 推荐
		if(empty($attrs) || in_array('recomm_ids', $attrs)){
			$recom_div_node = get_unique_element_by_class($doc, 'div', 'recommendations-bd');
			$recom_div_html = !empty($recom_div_node)? $recom_div_node->C14N():'';
			if(!empty($recom_div_html)) {
				$pattern = '#href="https://movie.douban.com/subject/(\d+)/\?from=subject-page"#U';
				$matches = array();
				preg_match_all($pattern, $recom_div_html, $matches);
				if(!empty($matches[1])) {
					$ret['recomm_ids'] = $matches[1];
				}
			}
		}

//		// 评论
//		if(empty($attrs) || in_array('comments', $attrs)){
//			$pattern = '#<div class="comment">([\s\S]*)</div>#U';
//			$matches = array();
//			preg_match_all($pattern, $html, $matches);
//			if(!empty($matches[1])) {
//				$comments = array();
//				foreach($matches[1] as $comment_html) {
//					$tmp_comment = array();
//					$pattern = '#class="">([\s\S]*)</a>#U';
//					$matches = array();
//					preg_match($pattern, $comment_html, $matches);
//					if(!empty($matches[1])) {
//						$tmp_comment['user'] = $matches[1];
//					}
//
//					$pattern = '#<p class=""> ([\s\S]*)</p>#U';
//					$matches = array();
//					preg_match($pattern, $comment_html, $matches);
//					if(!empty($matches[1])) {
//						$tmp_comment['content']= $matches[1];
//					}
//
//					if(!empty($tmp_comment['content'])) {
//						$comments[] = $tmp_comment;
//					}
//				}
//
//				$ret['comments'] = $comments;
//			}
//		}

		// 默认
		if(!empty($ret)){
			$ret['link'] = "https://movie.douban.com/subject/{$douban_id}/";
			$ret['id'] = $douban_id;
		}
		
		return $ret;
	}

}