#!/usr/bin/php -q
<?php
require_once('DrupalServiceAPIClient.class.php');

$c=new DrupalServiceAPIClient('http://drupal.ubctp.tal.org/api/v1');

try {
	$r=$c->create_node('product', 'PHP Test');
	print_r($r);
} catch (Exception $e) {
	die($e->getCode().' : '.$e->getMessage());
}

printf("Node %d created\n", $r->nid);

try {
	$node=$c->retrieve_node($r->nid);
	print_r($node);
} catch (Exception $e) {
	die($e->getCode().' : '.$e->getMessage());
}

printf("Loading all nodes\n");

try {
	$nodes=$c->index_nodes();
	print_r($nodes);
} catch (Exception $e) {
	die($e->getCode().' : '.$e->getMessage());
}

?>
