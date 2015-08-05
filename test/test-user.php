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

$u=$c->index_users();
print_r($u);

$first=$c->retrieve_user(1);
print_r($first);

try {
	$fields=array('body'=>
			array('und'=>
				array(0=>array(
					'value'=>'This is some body text.',
					'summary'=>'API User owned node'
				)
			)
		)
	);
	$r=$c->create_node('product', 'API test user owned node', $fields);
	print_r($r);
} catch (Exception $e) {
	die($e->getCode().' : '.$e->getMessage());
}


?>
