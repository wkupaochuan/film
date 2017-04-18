<?php
require_once(__DIR__ . '/base.php');

if($argc !== 3) {
	c_echo('eg: php douban_detail.php input_file_path output_file_path');
	exit;
}

$input_file_path = $argv[1];
$output_file_path = $argv[2];
$ifp = fopen($input_file_path, 'r');
$ofp = fopen($output_file_path, 'a+');

while(!feof($ifp)) {
	$line = fgets($ifp);
	$line = trim($line);
	if(empty($line)) {
		continue;
	}

	$list = json_decode($line, true);
	if(!empty($list)) {
		foreach($list as $film_digest) {
			if(empty($film_digest['link'])){
				log_error(__FUNCTION__, __LINE__, 'empty link:' . $line);
				continue;
			}
			$film_detail = douban_detail($film_digest);
			if(!empty($film_detail)) {
				fputs($ofp, json_encode($film_detail) . PHP_EOL);
			}else {
				log_error(__FUNCTION__, __LINE__, 'get noting on page:' . $line);
			}
			sleep(5);
		}
	}else {
		log_error(__FUNCTION__, __LINE__, 'empty digest:' . $line);
	}
}

fclose($ifp);
fclose($ofp);

function douban_detail($film_digest) {
	$ret = array();

	if(empty($film_digest['link'])) {
		return $ret;
	}
	//$html = file_get_contents('/home/wangchuanchuan/tmp/douban_detail.html');
	$html = file_get_contents($film_digest['link']);
	if(strlen($html) < 300 || strpos($html, '你想访问的页面不存在') !== false) {
		return $ret;
	}

	// id
	$pattern = '#https://movie.douban.com/subject/(\d+)/#U';
	$matches = array();
	preg_match($pattern, $html, $matches);
	if(!empty($matches[1])) {
		$ret['id'] = $matches[1];
	}

	//
	$ret['link'] = $film_digest['link'];
//	$ret['post_cover'] = $film_digest['post_cover'];

	// 主名称
	$pattern = '#<span property="v:itemreviewed">([\s\S]*)</span>#U';
	$matches = array();
	preg_match($pattern, $html, $matches);
	if(!empty($matches[1])) {
		$index = strpos($matches[1], ' ');
		if($index === false) {
			$ret['ch_name'] = $or_name = trim($matches[1]);
		}else {
			$ret['ch_name'] = trim(substr($matches[1], 0, $index));
			$ret['or_name'] = trim(substr($matches[1], $index));
		}
	}

	if(empty($ret['ch_name']))
	{
		return array();
	}

	// 海报
	$pattern = '#<div id="mainpic"([\s\S]*)</div>#U';
	$matches = array();
	preg_match($pattern, $html, $matches);
	if(!empty($matches[1])) {
		$post_cover_html = $matches[1];
		$pattern = '#src="([\s\S]*)"#U';
		$matches = array();
		preg_match($pattern, $post_cover_html, $matches);
		if(!empty($matches)){
			$ret['post_cover'] = $matches[1];
		}
	}


	// 其他名称
	$pattern = '#<span class="pl">又名:</span>([\s\S]*)<br/>#U';
	$matches = array();
	preg_match($pattern, $html, $matches);
	if(!empty($matches[1])) {
		$names = explode('/', $matches[1]);
		$ret['other_names'] = $names;
	}

	// 年份
	$pattern = '#<span class="year">\(([\s\S]*)\)</span>#U';
	$matches = array();
	preg_match($pattern, $html, $matches);
	if(!empty($matches[1])) {
		$ret['year'] = $matches[1];
	}


	// 导演
	$pattern = '#rel="v:directedBy">([\s\S]*)</a>#U';
	$matches = array();
	preg_match($pattern, $html, $matches);
	if(!empty($matches[1])) {
		$ret['director'] = $matches[1];
	}

	// 主演
	$pattern = '#<span class="actor">([\s\S]*)<br/>#U';
	$matches = array();
	preg_match($pattern, $html, $matches);
	if(!empty($matches[1])) {
		$actors_html = $matches[1];
		$pattern = '#rel="v:starring">([\s\S]*)</a>#U';
		$matches = array();
		preg_match_all($pattern, $actors_html, $matches);
		if(!empty($matches[1])) {
			$ret['actors'] = $matches[1];
		}
	}


	// 类型
	$pattern = '#<span property="v:genre">([\s\S]*)</span>#U';
	$matches = array();
	preg_match_all($pattern, $html, $matches);
	if(!empty($matches[1])) {
		$ret['genre'] = $matches[1];
	}

	// 片长
	$pattern = '#<span property="v:runtime" content="(\d+)">#U';
	$matches = array();
	preg_match($pattern, $html, $matches);
	if(!empty($matches[1])) {
		$ret['runtime'] = $matches[1] . '分钟';
	}

	// 评分
	$pattern = '#property="v:average">(\d.\d)</strong>#U';
	$matches = array();
	preg_match($pattern, $html, $matches);
	if(!empty($matches[1])) {
		$ret['rate'] = $matches[1];
	}

	// 简介
	$pattern = '#<span property="v:summary" class="">([\s\S]*)</span>#U';
	$matches = array();
	preg_match($pattern, $html, $matches);
	if(!empty($matches[1])) {
		$ret['summary'] = trim($matches[1]);
	}

	// 相关图片
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

	// 获奖情况
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

	// 推荐
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

	// 评论
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

	return $ret;
}

exit();