#!/usr/bin/php -q
<?php
require_once('DrupalServiceAPIClient.class.php');

$c=new DrupalServiceAPIClient('http://drupal.ubctp.tal.org/api/v1');
$c->set_debug(true);

try {
	$c->set_auth_type(AUTH_SESSION);
	$c->set_auth('apiuser','apiuserpassword123');
	$c->login();
} catch (Exception $e) {
	die($e->getCode().' : '.$e->getMessage());
}

?>
