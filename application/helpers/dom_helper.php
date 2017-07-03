<?php

/**
 * 获取指定的子标签
 * @param DOMNode $node
 * @param $tag
 * @return array(DOMNode)
 */
function get_child_nodes_by_tag(DOMNode $node, $tag){
	$node_arr = array();

	$dom_node_list = $node->childNodes;

	for($i = 0; $i < $dom_node_list->length; $i++){
		$node = $dom_node_list->item($i);
		if(strtolower($node->nodeName) == strtolower($tag)){
			$node_arr[] = $node;
		}
	}

	return $node_arr;
}

/**
 * @param DOMDocument $doc
 * @param $tag
 * @param $class_name
 * @return DOMElement
 */
function get_unique_element_by_class(DOMDocument $doc, $tag, $class_name){
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