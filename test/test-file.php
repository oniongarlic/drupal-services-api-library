#!/usr/bin/php -q
<?php
require_once('../lib/DrupalServiceAPIClient.class.php');

$c=new DrupalServiceAPIClient('http://drupal.ubctp.tal.org/api/v1');
$fid=0;

/*
try {
	printf("Uploading text file\n");
	$r=$c->upload_file('upload-test-file.txt');
	print_r($r);
} catch (Exception $e) {
	die($e->getCode().' : '.$e->getMessage());
}
*/

try {
	printf("Uploading image file\n");
	$r=$c->upload_file('upload-test-image.jpg', true);
	print_r($r);
	$fid=$r->fid;
} catch (Exception $e) {
	die($e->getCode().' : '.$e->getMessage());
}

try {
	printf("Adding a node with uploaded image file %d\n", $fid);
	$fields=array(
		'body'=>array(DRUPAL_LANGUAGE_NONE=>
			array(array(
				'value'=>'This node has an image attached',
				'summary'=>'Imageeeeeeeeeeeee!!!'
			))
		),
		'field_image'=>array(DRUPAL_LANGUAGE_NONE=>
			array(0=>array(
				'fid'=>(int)$fid,
				'alt'=>'Test image alt',
				'title'=>'A uploaded and attached to node field image',
				'display'=>'1',
				'_weight'=>1
			),
			1=>array(
				'fid'=>0,
				'alt'=>'Test image alt',
				'title'=>'A uploaded and attached to node field image',
				'display'=>'1',
				'_weight'=>2
			)
			
			)
		),
		'field_weight'=>array(DRUPAL_LANGUAGE_NONE=>
			array(array(
				'weight'=>512
			))
		)
	);
	$c->set_debug(true);
        $r=$c->create_node('product', 'Image upload test page', $fields);
        print_r($r);
} catch (Exception $e) {
        die($e->getCode().' : '.$e->getMessage());
}

try {
	$node=$c->retrieve_node($r->nid);
	print_r($node);
} catch (Exception $e) {
	die($e->getCode().' : '.$e->getMessage());
}

?>
