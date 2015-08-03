#!/usr/bin/php -q
<?php
require_once('DrupalServiceAPIClient.class.php');

$c=new DrupalServiceAPIClient('http://drupal.ubctp.tal.org/api/v1');
try {
	printf("Uploading text file\n");
	$r=$c->upload_file('upload-test-file.txt');
	print_r($r);
} catch (Exception $e) {
	die($e->getCode().' : '.$e->getMessage());
}

try {
	printf("Uploading image file\n");
	$r=$c->upload_file('upload-test-image.jpg');
	print_r($r);
} catch (Exception $e) {
	die($e->getCode().' : '.$e->getMessage());
}

?>
