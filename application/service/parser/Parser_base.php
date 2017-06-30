<?php

class Parser_base extends MY_Service{

    public function __construct(){
        parent::__construct();
    }

    /**
     * @param DOMDocument $doc
     * @param $class_name
     * @param $tag
     * @return DOMElement
     */
    protected function _get_element_by_class(DOMDocument $doc, $tag, $class_name){
        $node_arr = array();
        $domnode_list = $doc->getElementsByTagName($tag);
        if(!empty($domnode_list)){
            for($i = 0; $i < $domnode_list->length; ++$i){
                $node_class = $domnode_list->item($i)->getAttribute('class');
                if(!empty($node_class) && $node_class == $class_name){
                    $node_arr[] = $domnode_list->item($i);
                }
            }
        }

        return count($node_arr) == 1? $node_arr[0]:null;
    }

    /**
     * @param DOMDocument $doc
     * @param $tag_name
     * @return DOMElement|null
     */
    protected function _get_unique_element_by_tag(DOMDocument $doc, $tag_name){
        $node_list = $doc->getElementsByTagName($tag_name);
        return $node_list->length == 1? $node_list->item(0):null;
    }

    /**
     * 组装解析完的html
     * @param $head_dom_el_arr
     * @param $body_el_arr
     * @return string
     */
    protected function _pack_des_html($head_dom_el_arr, $body_el_arr){
        $html = <<<HTML
	<!DOCTYPE html>
	<html lang="zh-cmn-Hans" class="ua-windows ua-webkit">
    <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
HTML;

        foreach($head_dom_el_arr as $tmp_dom_el){
            if(!empty($tmp_dom_el)){
                $html .= PHP_EOL . $tmp_dom_el->C14N();
            }
        }
        $html .= PHP_EOL . '</head>' . PHP_EOL . '<body>';

        foreach($body_el_arr as $tmp_dom_el){
            if(!empty($tmp_dom_el)){
                $html .= PHP_EOL . $tmp_dom_el->C14N();
            }
        }
        $html .= PHP_EOL . '</body>' . PHP_EOL . '</html>';

        return $html;
    }

}