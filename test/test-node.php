#!/usr/bin/php -q
<?php
require_once('../lib/DrupalServiceAPIClient.class.php');

$c=new DrupalServiceAPIClient('http://drupal.ubctp.tal.org/api/v1');
$c->set_debug(true);
try {
	$c->set_auth_type(AUTH_SESSION);
	$c->set_auth('apiuser','apiuserpassword123');
	$c->login();
} catch (Exception $e) {
	die($e->getCode().' : '.$e->getMessage());
}

function load_node(DrupalServiceAPIClient $c, $nid)
{
try {
	$node=$c->retrieve_node($nid);
	print_r($node);
} catch (Exception $e) {
	die($e->getCode().' : '.$e->getMessage());
}
}

try {
	$fields=array('body'=>
			array('und'=>array(array(
					'value'=>'This is some body text.',
					'summary'=>'A summary text for body field'
					)
				)
		),
		'field_sku'=>array(DRUPAL_LANGUAGE_NONE=>
			array(array('value'=>'DUMMY-TEST-SKU-123456789'))
		)
	);
	$r=$c->create_node('product', 'PHP Test', $fields);
	print_r($r);
} catch (Exception $e) {
	die($e->getCode().' : '.$e->getMessage());
}

printf("Node %d created\n", $r->nid);
load_node($c, $r->nid);

printf("Update node %d\n", $r->nid);
try {
	$node=$c->update_node($r->nid, 'Updated PHP test', array());
	print_r($node);
} catch (Exception $e) {
	die($e->getCode().' : '.$e->getMessage());
}

load_node($c, $r->nid);

printf("Loading product nodes\n");
try {
	$nodes=$c->index_nodes(0, 20, null, array('type'=>'product'));
	print_r($nodes);
} catch (Exception $e) {
	die($e->getCode().' : '.$e->getMessage());
}

printf("Removing test node %d\n", $r->nid);
$node=$c->delete_node($r->nid);
print_r($node);

?>
