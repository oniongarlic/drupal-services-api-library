#!/usr/bin/php -q
<?php
require_once('../lib/DrupalServiceAPIClient.class.php');

$c=new DrupalServiceAPIClient('http://drupal.ubctp.tal.org/api/v1');
$fid=0;

function upload_file(DrupalServiceAPIClient $c, $file)
{
try {
	printf("Uploading file %s\n", $file);
	$r=$c->upload_file($file, true);
	print_r($r);
	return $r->fid;
} catch (Exception $e) {
	die($e->getCode().' : '.$e->getMessage());
}
}

$fid1=upload_file($c, 'upload-test-image-1.jpg');
// $fid2=upload_file($c, 'upload-test-image-2.png');

try {
	printf("Adding a product with uploaded image file %d\n", $fid2);
	/*
	$fields=array(
		'field_image'=>array(DRUPAL_LANGUAGE_NONE=>
			array(0=>array(
				'fid'=>(int)$fid2,
				'alt'=>'Test image alt',
				'title'=>'A uploaded and attached to node field image',
				'display'=>'1',
				'_weight'=>1
			),
			1=>array(
				'fid'=>(int)$fid3,
				'alt'=>'Test image alt',
				'title'=>'A uploaded and attached to node field image',
				'display'=>'1',
				'_weight'=>2
			)
			)
		)
	);
	*/
	$c->set_debug(true);

	// Simple
        $r1=$c->create_product('product', 'SKU-1234567890', 'Product add test 1', 10000);
        print_r($r1);

	// Flattened format ?
	$images=array();
	$images[]=array('fid'=>$fid1);
	$fields=array('field_image'=>$images);

        $r2=$c->create_product('product', 'SKU-1234567891', 'Product add test 2', 1234567, $fields);
        print_r($r2);

	$c->delete_product($r1->product_id);
	$c->delete_product($r2->product_id);
} catch (Exception $e) {
        die($e->getCode().' : '.$e->getMessage());
}

/*
try {
	$node=$c->retrieve_product($r->nid);
	print_r($node);
} catch (Exception $e) {
	die($e->getCode().' : '.$e->getMessage());
}
*/

?>
