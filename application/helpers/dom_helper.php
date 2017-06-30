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