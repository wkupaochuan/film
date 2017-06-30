<?php
require_once(APPPATH . 'service/extracter/Extracter_base.php');

class Extracter_douban extends Extracter_base{

	public function __construct(){
		parent::__construct();

		$this->load->service('parser/Parser_douban');
	}

	public function process($douban_id, $from_remote = false){
		$ret = array();

		if(empty($douban_id)){
			return $ret;
		}

		if($from_remote || !$this->Parser_douban->exist($douban_id)){
			$this->Parser_douban->process($douban_id);
		}

		if(!$this->Parser_douban->exist($douban_id)){
			return $ret;
		}

		$ret = $this->_extract_detail($douban_id);

		return $ret;
	}

	/**************************************private methods****************************************************************************/

	private function _extract_detail($douban_id, $attrs = array()) {
		$ret = array();
		$doc = new DOMDocument();
		@$doc->loadHTMLFile($this->Parser_douban->cal_path($douban_id));

		// 主名称
		if(empty($attrs) || in_array('ch_name', $attrs)){
			$title_node = $doc->getElementsByTagName('title');
			if(!empty($title_node) && $title_node->length == 1){
				$title_node = $title_node->item(0);
				$ch_name = trim(str_replace('(豆瓣)', '', $title_node->nodeValue));
				if(!empty($ch_name)){
					$ret['ch_name'] = $ch_name;

					$h1_node = $doc->getElementsByTagName('h1');
					if(!empty($h1_node) && $h1_node->length == 1){
						$span_nodes = get_child_nodes_by_tag($h1_node->item(0), 'span');
						if(count($span_nodes) === 2){
							$compact_name = trim($span_nodes[0]->nodeValue);
							if(strlen($ch_name) !== strlen($compact_name)){
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
			!empty($main_pic_div_node) && $main_pic_div_node->getElementsByTagName('img');
			if(!empty($main_pic_div_node) && $main_pic_div_node->length == 1){
				$post_cover_url = trim($main_pic_div_node->item(0)->getAttribute('src'));
				if(!empty($post_cover_url)){
					$ret['post_cover'] = $post_cover_url;
				}
			}

			if(empty($ret['post_cover'])) {
				f_log_error('no post_cover ' . $douban_id);
			}
		}

		// 年份
		if(empty($attrs) || in_array('year', $attrs)){
			$pattern = '#<span class="year">\(([\s\S]*)\)</span>#U';
			$matches = array();
			preg_match($pattern, $html, $matches);
			if(!empty($matches[1])) {
				$ret['year'] = $matches[1];
			}

			if(empty($ret['year'])) {
				f_log_error('no year ' . $douban_id);
			}
		}

		// 其他名称
		if(empty($attrs) || in_array('other_names', $attrs)){
			$pattern = '#<span class="pl">又名:</span>([\s\S]*)<br/>#U';
			$matches = array();
			preg_match($pattern, $html, $matches);

			!empty($matches[1]) && $names = explode('/', $matches[1]);
			if(!empty($names)){
				foreach($names as $tmp_name){
					$tmp_name = trim($tmp_name);
					!empty($tmp_name) && $ret['other_names'][] = trim($tmp_name);
				}
			}
		}

		// 导演
		if(empty($attrs) || in_array('director', $attrs)){
			$pattern = '#rel="v:directedBy">([\s\S]*)</a>#U';
			$matches = array();
			preg_match($pattern, $html, $matches);
			if(!empty($matches[1])) {
				$ret['director'] = trim($matches[1]);
			}
		}

		// 主演
		if(empty($attrs) || in_array('actors', $attrs)){
			$pattern = '#<span class="actor">([\s\S]*)<br/>#U';
			$matches = array();
			preg_match($pattern, $html, $matches);
			if(!empty($matches[1])) {
				$actors_html = $matches[1];
				$pattern = '#rel="v:starring">([\s\S]*)</a>#U';
				$matches = array();
				preg_match_all($pattern, $actors_html, $matches);
				if(!empty($matches[1])) {
					$actors = $matches[1];
					if(!empty($actors)){
						foreach($actors as $tmp_actor){
							$tmp_actor = trim($tmp_actor);
							!empty($tmp_actor) &&$ret['actors'][] = trim($tmp_actor);
						}
					}
				}
			}
		}

		// 类型
		if(empty($attrs) || in_array('genre', $attrs)){
			$pattern = '#<span property="v:genre">([\s\S]*)</span>#U';
			$matches = array();
			preg_match_all($pattern, $html, $matches);
			if(!empty($matches[1])) {
				$ret['genre'] = $matches[1];
			}
		}

		// 片长
		if(empty($attrs) || in_array('runtime', $attrs)){
			$pattern = '#<span property="v:runtime" content="(\d+)">#U';
			$matches = array();
			preg_match($pattern, $html, $matches);
			if(!empty($matches[1])) {
				$ret['runtime'] = $matches[1] . '分钟';
			}
		}

		// 评分
		if(empty($attrs) || in_array('rate', $attrs)){
			$pattern = '#property="v:average">(\d.\d)</strong>#U';
			$matches = array();
			preg_match($pattern, $html, $matches);
			if(!empty($matches[1])) {
				$ret['rate'] = $matches[1];
			}
		}

		// 简介
		if(empty($attrs) || in_array('summary', $attrs)){
			$pattern = '#<span property="v:summary" class="">([\s\S]*)</span>#U';
			$matches = array();
			preg_match($pattern, $html, $matches);
			if(!empty($matches[1])) {
				$ret['summary'] = trim($matches[1]);
			}
		}

		// 相关图片
		if(empty($attrs) || in_array('related_pics', $attrs)){
			$pattern = '#<ul class="related-pic-bd([\s\S]*)</ul>#U';
			$matches = array();
			preg_match($pattern, $html, $matches);
			if(!empty($matches[1])) {
				$related_pics_html = trim($matches[1]);
				$pattern = '#<img src="([\s\S]*)"#U';
				$matches = array();
				preg_match_all($pattern, $related_pics_html, $matches);
				if(!empty($matches[1])) {
					$ret['related_pics'] = array_slice($matches[1], 1);
				}
			}
		}

		// 获奖情况
		if(empty($attrs) || in_array('awards', $attrs)){
			$pattern = '#<ul class="award">([\s\S]*)</ul>#U';
			$matches = array();
			preg_match_all($pattern, $html, $matches);
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
						$award['who'] = substr($matches[1][2], stripos($matches[1][2], '>'), strrpos($matches[1][2], '<') - stripos($matches[1][2], '>') );
						if(!empty($award)) $awards[] = $award;
					}
				}
				$ret['awards'] = $awards;
			}
		}

		// 推荐
		if(empty($attrs) || in_array('recomm_ids', $attrs)){
			$pattern = '#<div class="recommendations-bd">([\s\S]*)</div>#U';
			$matches = array();
			preg_match($pattern, $html, $matches);
			if(!empty($matches[1])) {
				$recomm_html = trim($matches[1]);
				$pattern = '#href="https://movie.douban.com/subject/(\d+)/\?from=subject-page"#U';
				$matches = array();
				preg_match_all($pattern, $recomm_html, $matches);
				if(!empty($matches[1])) {
					$ret['recomm_ids'] = $matches[1];
				}
			}
		}

		// 评论
		if(empty($attrs) || in_array('comments', $attrs)){
			$pattern = '#<div class="comment">([\s\S]*)</div>#U';
			$matches = array();
			preg_match_all($pattern, $html, $matches);
			if(!empty($matches[1])) {
				$comments = array();
				foreach($matches[1] as $comment_html) {
					$tmp_comment = array();
					$pattern = '#class="">([\s\S]*)</a>#U';
					$matches = array();
					preg_match($pattern, $comment_html, $matches);
					if(!empty($matches[1])) {
						$tmp_comment['user'] = $matches[1];
					}

					$pattern = '#<p class=""> ([\s\S]*)</p>#U';
					$matches = array();
					preg_match($pattern, $comment_html, $matches);
					if(!empty($matches[1])) {
						$tmp_comment['content']= $matches[1];
					}

					if(!empty($tmp_comment['content'])) {
						$comments[] = $tmp_comment;
					}
				}

				$ret['comments'] = $comments;
			}
		}

		// 默认
		if(!empty($ret)){
			$ret['link'] = "https://movie.douban.com/subject/{$douban_id}/";
			$ret['id'] = $douban_id;
		}

		return $ret;
	}

}