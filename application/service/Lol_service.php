<?php
class Lol_service extends MY_Service{

    /**************************************public methods****************************************************************************/

	/**
	 * 爬取
	 * @param $short_url
	 * @return bool
	 */
	public function craw($short_url){
        $url = 'http://www.loldytt.com/' . $short_url . '/';
        $html = $this->_get_film_html($url);
        $film_detail = $this->_extract_film_detail($html);
	}

    /**************************************private methods****************************************************************************/

    /**
     * 根据url获取Html(有反爬取机制, 需要根据返回的内容拼接url然后进行多次递归组装, 直到返回争取的html)
     * @param $encryptUrl
     * @param string $param
     * @param int $level
     * @return string
     */
    private function _get_film_html($encryptUrl, $param = '', $level = 0) {
        $html = '';

        if(empty($encryptUrl) || $level >= 50) {
            return $html;
        }

        $cookie_file_path = '/tmp/lol_cookie.txt';
        static $cookie_time;
        if(empty($cookie_time) || (time() - $cookie_time) > 3){
            $cookie_time = time();
            file_put_contents($cookie_file_path, '');
        }

        $html = $param == ''? f_curl($encryptUrl, array(), $cookie_file_path):f_curl($encryptUrl . '?' . $param, array(), $cookie_file_path);
        $html = iconv(mb_detect_encoding($html,array('UTF-8','GBK','GB2312')), 'UTF-8', $html);
        if($this->_check_for_lol_detail_html($html)){
            return $html;
        }

        $pattern = '#location([\s\S]*)</scri#';
        $matches = array();
        preg_match($pattern, $html, $matches);
        if(!empty($matches) && !empty($matches[1])) {
            $param = $matches[1];
            $tmp = explode('?', $param);
            $param = $tmp[1];
            $param = str_replace(array('"', '+', ';', ' '), '', $param);

            return $this->_get_film_html($encryptUrl, $param, $level + 1);
        }else{
            f_log_error('error on parsing lol html pending :' . $html);
            return '';
        }
    }

    /**
     * 校验是否是正确的详情页面
     * @param $html
     * @return bool
     */
    private function _check_for_lol_detail_html($html = ''){
        $pattern = '<div class="biaoti">';
        return strpos($html, $pattern) !== false;
    }
    
    /**
     * 爬取详情
     * @param $html
     * @return array
     */
    private function _extract_film_detail($html){
        $film_detail = array();

        // 标题块
        $pattern = '#<div class="biaoti">([\s\S]*)</ul>#U';
        $matches = array();
        preg_match($pattern, $html, $matches);
        if(!empty($matches) && !empty($matches[1])) {
            $biaoti_html = $matches[0];

            $pattern = '#<h1>([\s\S]*)</h1>#U';
            $matches = array();
            preg_match($pattern, $biaoti_html, $matches);
            if(!empty($matches) && !empty($matches[1])) {
                $tmp_html = $matches[0];

                // 中文分类
                $pattern = '#<h1>([\s\S]*)<a href=#U';
                $matches = array();
                preg_match($pattern, $tmp_html, $matches);
                if(!empty($matches) && !empty($matches[1])) {
                    $film_detail['ch_cat'] = trim($matches[1]);
                    $film_detail['ch_cat'] = explode(' ', $film_detail['ch_cat']);
                    $film_detail['ch_cat'] = $film_detail['ch_cat'][0];
                }

                // 中文名称
                $pattern = '#">([\s\S]*)</a>#U';
                $matches = array();
                preg_match($pattern, $tmp_html, $matches);
                if(!empty($matches) && !empty($matches[1])) {
                    $film_detail['ch_name'] = str_replace('：', ':', trim($matches[1]));
                }

                // lol 更新时间
                $pattern = '#<p>([\s\S]*)</p>#U';
                $matches = array();
                preg_match($pattern, $tmp_html, $matches);
                if(!empty($matches) && !empty($matches[1])) {
                    $film_detail['lol_up_time'] = strtotime($matches[1]);
                }

            }

            // 主演
            $pattern = '#<li>主　演 ：([\s\S]*)</li>#U';
            $matches = array();
            preg_match($pattern, $biaoti_html, $matches);
            if(!empty($matches) && !empty($matches[1])) {
                $actors = explode(' ', $matches[1]);
                foreach($actors as $actor){
                    $actor = trim($actor);
                    if(!empty($actor)){
                        $film_detail['actors'][] = $actor;
                    }
                }
            }
        }

        // 内容块
        $pattern = '#<div class="neirong">([\s\S]*)</div>#U';
        $matches = array();
        preg_match($pattern, $html, $matches);
        if(!empty($matches) && !empty($matches[1])) {
            $neirong_html = $matches[0];

            // 导演
            $pattern = '#<p>导演: ([\s\S]*)<br>#U';
            $matches = array();
            preg_match($pattern, $neirong_html, $matches);
            if(!empty($matches) && !empty($matches[1])) {
                $directors = $this->_parse_person_for_lol($matches[1]);
                !empty($directors) &&  $film_detail['directors'] = $directors;
            }

            // 编剧
            $pattern = '#<br>编剧: ([\s\S]*)<br>#U';
            $matches = array();
            preg_match($pattern, $neirong_html, $matches);
            if(!empty($matches) && !empty($matches[1])) {
                $writers = $this->_parse_person_for_lol($matches[1]);
                !empty($writers) &&  $film_detail['writers'] = $writers;
            }

            // 演员
            $pattern = '#<br>主演: ([\s\S]*)<br>#U';
            $matches = array();
            preg_match($pattern, $neirong_html, $matches);
            if(!empty($matches) && !empty($matches[1])) {
                $actors = $this->_parse_person_for_lol($matches[1]);
                !empty($actors) &&  $film_detail['actors'] = $actors;
            }

            // 类型
            $pattern = '#<br>类型: ([\s\S]*)<br>#U';
            $matches = array();
            preg_match($pattern, $neirong_html, $matches);
            if(!empty($matches) && !empty($matches[1])) {
                $genres = $this->_parse_person_for_lol($matches[1]);
                !empty($genres) &&  $film_detail['genres'] = $genres;
            }

            // 语言
            $pattern = '#<br>语言: ([\s\S]*)<br>#U';
            $matches = array();
            preg_match($pattern, $neirong_html, $matches);
            if(!empty($matches) && !empty($matches[1])) {
                $langs = $this->_parse_person_for_lol($matches[1]);
                !empty($langs) &&  $film_detail['langs'] = $langs;
            }

            // 国家
            $pattern = '#<br>制片国家/地区: ([\s\S]*)<br>#U';
            $matches = array();
            preg_match($pattern, $neirong_html, $matches);
            if(!empty($matches) && !empty($matches[1])) {
                $countries = $this->_parse_person_for_lol($matches[1]);
                !empty($countries) &&  $film_detail['countries'] = $countries;
            }

            // 上映日期
            $pattern = '#<br>上映日期: ([\s\S]*)<br>#U';
            $matches = array();
            preg_match($pattern, $neirong_html, $matches);
            if(!empty($matches) && !empty($matches[1])) {
                $pub_times = $this->_parse_pub_time_for_lol($matches[1]);
                !empty($pub_times) &&  $film_detail['pub_times'] = $pub_times;
                if(!empty($pub_times)){
                    $times = array_keys($pub_times);
                    sort($times);
                    $film_detail['year'] = date('Y-m-d', $times[0]);
                }
            }

            // 其他名称
            $pattern = '#<br>又名: ([\s\S]*)<br>#U';
            $matches = array();
            preg_match($pattern, $neirong_html, $matches);
            if(!empty($matches) && !empty($matches[1])) {
                $other_names = $this->_parse_person_for_lol($matches[1]);
                !empty($other_names) &&  $film_detail['other_names'] = $other_names;
            }

        }


        // 相关推荐
        $pattern = '#<div class="tu">([\s\S]*)</div>#U';
        $matches = array();
        preg_match($pattern, $html, $matches);
        if(!empty($matches) && !empty($matches[1])) {
            $tmp_html = $matches[1];
            $pattern = '#<a href="([\s\S]*)">#U';
            $matches = array();
            preg_match_all($pattern, $tmp_html, $matches);
            if(!empty($matches) && !empty($matches[1])) {
                $recom = array();
                foreach($matches[1] as $tmp){
                    if(!empty($tmp)){
                        $path = substr($tmp, strpos($tmp, 'com') + 4);
                        $path = trim($path, '/');
                        array_push($recom, $path);
                    }
                }
                if(!empty($recom)){
                    $film_detail['recom'] = $recom;
                }
            }
        }

        // 时间
        $pattern = '#<br>上映日期:([\s\S]*)<br>#U';
        $matches = array();
        preg_match($pattern, $html, $matches);
        if(!empty($matches) && !empty($matches[1])) {
            $part_html = $matches[1];
            $pattern = '#([\d]{4}-[\d]{2}-[\d]{2})#U';
            $matches = array();
            preg_match_all($pattern, $part_html, $matches);
            if(!empty($matches) && !empty($matches[1])) {
                $full_time = $matches[1][count($matches[1]) - 1];
                $ret['year'] = date('Y', strtotime($full_time));
            }
        }


        // 相关推荐
        $pattern = '#<div class="tu">([\s\S]*)</div>#U';
        $matches = array();
        preg_match($pattern, $html, $matches);
        if(!empty($matches) && !empty($matches[1])) {
            $tmp_html = $matches[1];
            $pattern = '#<a href="([\s\S]*)">#U';
            $matches = array();
            preg_match_all($pattern, $tmp_html, $matches);
            if(!empty($matches) && !empty($matches[1])) {
                $recom = array();
                foreach($matches[1] as $tmp){
                    if(!empty($tmp)){
                        $path = substr($tmp, strpos($tmp, 'com') + 4);
                        $path = trim($path, '/');
                        array_push($recom, $path);
                    }
                }
                if(!empty($recom)){
                    $ret['recom'] = $recom;
                }
            }
        }

        // 迅雷资源&magnet资源
        $thunders = $this->_extract_film_thunder_links($html);
        if(!empty($thunders)){
            $film_detail = array_merge($film_detail, $thunders);
        }

        // bt资源
        $bts = $this->_extract_film_BT_links($html);
        if(!empty($bts)){
            $film_detail['bt'] = $bts;
        }

        print_r($film_detail);exit;
        return $film_detail;
    }

    /**
     * 从电影详情页提取迅雷下载链接
     * @param $html
     * @return array [{'title', 'link'}];
     */
    private function _extract_film_thunder_links($html){
        $ret = array(
            'thunder' => array(),
            'magnet' => array(),
        );

        if(empty($html)) {
            return $ret;
        }

        $pattern = '#<div id="[a-z]?jishu">([\s\S]*)</ul>#U';
        $matches = array();
        preg_match_all($pattern, $html, $matches);
        if(!empty($matches) && !empty($matches[1])) {
            foreach($matches[1] as $html){
                $linkPattern = '#href="([\s\S]*)"#U';
                $titlePattern = '#title="([\s\S]*)"#U';
                $linkMatches = array();
                $titleMatches = array();
                preg_match_all($linkPattern, $html, $linkMatches);
                preg_match_all($titlePattern, $html, $titleMatches);
                if(!empty($linkMatches) && !empty($linkMatches[1]) && !empty($titleMatches) && !empty($titleMatches[1]) && count($linkMatches[1]) === count($titleMatches[1]) ) {
                    $bt = array();
                    for($i = 0; $i < count($linkMatches[1]); ++$i) {
                        $bt[] = array(
                            'title' => $titleMatches[1][$i],
                            'link' => $linkMatches[1][$i],
                        );
                    }

                    if(strpos($bt[0]['link'], 'thunder:') !== false){
                        array_push($ret['thunder'], $bt);
                    }else if(strpos($bt[0]['link'], 'magnet:') !== false){
                        array_push($ret['magnet'], $bt);
                    }
                }
            }
        }

        return $ret;
    }

    /**
     * 从电影详情页提取bt下载链接
     * @param $html
     * @return array
     */
    private function _extract_film_BT_links($html){
        $ret = array();

        if(empty($html)) {
            return $ret;
        }

        $pattern = '#<div id="bt">([\s\S]*)</ul>#U';
        $matches = array();
        preg_match_all($pattern, $html, $matches);
        if(!empty($matches) && !empty($matches[1])) {
            foreach($matches[1] as $html){
                $linkPattern = '#href="([\s\S]*)"#U';
                $titlePattern = '#title="([\s\S]*)"#U';
                $linkMatches = array();
                $titleMatches = array();
                preg_match_all($linkPattern, $html, $linkMatches);
                preg_match_all($titlePattern, $html, $titleMatches);
                if(!empty($linkMatches) && !empty($linkMatches[1]) && !empty($titleMatches) && !empty($titleMatches[1]) && count($linkMatches[1]) === count($titleMatches[1]) ) {
                    $tmp = array();
                    for($i = 0; $i < count($linkMatches[1]); $i++) {
                        $tmp[] = array(
                            'title' => $titleMatches[1][$i],
                            'link' => $linkMatches[1][$i],
                        );
                    }
                    if(!empty($tmp)){
                        array_push($ret, $tmp);
                    }
                }
            }
        }

        return $ret;
    }

    /**
     * 解析人名字符串
     * @param $person_str
     * @return array
     */
    private function _parse_person_for_lol($person_str){
        $ret = array();
        foreach(explode('/', $person_str) as $tmp){
            $tmp = trim($tmp);
            if(!empty($tmp) && strpos($tmp, '更多') === false){
                $tmp = str_replace('：', ':', $tmp);
                $ret[] = $tmp;
            }
        }

        return $ret;
    }

    /**
     * 解析上映时间
     * @param $pub_time_str
     * @return array
     */
    private function _parse_pub_time_for_lol($pub_time_str){
        $ret = array();
        $pub_time_arr = $this->_parse_person_for_lol($pub_time_str);
        if(!empty($pub_time_arr)){
            foreach($pub_time_arr as $tmp){
                $tmp = explode('(', $tmp);
                $t = strtotime($tmp[0]);
                $ret[$t] = array(
                    'time' => $t,
                    'country' => isset($tmp[1])? trim($tmp[1], "\r\n\t)"):''
                );
            }
        }

        return $ret;
    }


}