<?php
/**
 *
 * This code is released under the GNU General Public License.
 *
 */

define('DRUPAL_LANGUAGE_NONE', 'und');

define('AUTH_ANONYMOUS', 0);
define('AUTH_BASIC', 1);
define('AUTH_SESSION', 2);

class DrupalServiceException extends Exception { }
class DrupalServiceAuthException extends DrupalServiceException { }

class DrupalServiceAPIClient
{
// API url
protected $url;
protected $debug=false;
protected $uid=0;

// Basic auth username and password
protected $auth=0;
protected $username;
protected $password;
private $session_cookie=null;
private $csrf_token=null;

// API key auth (WIP)
protected $apikey;

// Current language
protected $language;

// Product currency
protected $currency='EUR';

function __construct($url)
{
$this->url=$url;
$this->language=DRUPAL_LANGUAGE_NONE;
}

public function set_language($l)
{
$this->language=$l;
}

public function set_currency($c)
{
$this->currency=$c;
}

public function set_auth_type($t)
{
$this->auth=$t;
}

public function set_auth($username, $password)
{
if (!is_string($username))
	throw new DrupalServiceException('Invalid username', 500);
if (!is_string($password))
	throw new DrupalServiceException('Invalid password', 500);
$this->username=$username;
$this->password=$password;
}

public function login()
{
switch ($this->auth) {
	case AUTH_ANONYMOUS:
		return true;
	case AUTH_SESSION:
		return $this->login_session();
	break;
	case AUTH_BASIC:
		return true;	
	break;
	default:
		throw new DrupalServiceException('Unknown authentication selected', 0);
}
}

public function set_debug($bool)
{
$this->debug=$bool;
}

private function getcurl($url)
{
$curl=curl_init($url);
$header=array( 'Content-Type: application/json');
if (is_string($this->csrf_token))
	$header[]='X-CSRF-Token: '.$this->csrf_token;

$options=array(
	CURLOPT_HEADER => FALSE,
	CURLOPT_RETURNTRANSFER => TRUE,
	CURLINFO_HEADER_OUT => TRUE,
	CURLOPT_HTTPHEADER => $header);
curl_setopt_array($curl, $options);

switch ($this->auth) {
	case AUTH_BASIC:
	curl_setopt($curl, CURLOPT_HTTPAUT, CURLAUTH_BASIC);
	curl_setopt($curl, CURLOPT_USERPWD, $this->username.':'.$this->password);
	break;
	case AUTH_SESSION:
	if (is_string($this->session_cookie))
		curl_setopt($curl, CURLOPT_COOKIE, $this->session_cookie);
	break;
}

return $curl;
}

protected function dumpDebug($endpoint, $data=null)
{
if (!$this->debug)
	return;

printf("API Endpoint: %s\nData:\n", $endpoint);
print_r($data);
}

protected function handleStatus($status, $error, $response)
{
switch ($status) {
	case 0:
		throw new DrupalServiceException('CURL Error: '.$error, $status);
	case 200:
		return true;
	case 401:
		throw new DrupalServiceAuthException('Authentication error: '.$response, $status);
	default:
		throw new DrupalServiceException($response, $status);
}

}

protected function executeGET($endpoint, array $query=null)
{
$url=$this->url.'/'.$endpoint;
if (is_array($query))
	$url.='?'.http_build_query($query);

$curl=$this->getcurl($url);
curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');

$this->dumpDebug($endpoint);

$response=curl_exec($curl);
$status=curl_getinfo($curl, CURLINFO_HTTP_CODE);
$error=curl_error($curl);
curl_close($curl);

$this->handleStatus($status, $error, $response);

return $response;
}

protected function executeDELETE($endpoint)
{
$url=$this->url.'/'.$endpoint;
$curl=$this->getcurl($url);
curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');

$this->dumpDebug($endpoint);

$response=curl_exec($curl);
$status=curl_getinfo($curl, CURLINFO_HTTP_CODE);
$error=curl_error($curl);
curl_close($curl);

$this->handleStatus($status, $error, $response);

return $response;
}

protected function executePOST($endpoint, $data)
{
$url=$this->url.'/'.$endpoint;

$curl=$this->getcurl($url);
curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

$this->dumpDebug($endpoint, $data);

$response=curl_exec($curl);
$status=curl_getinfo($curl, CURLINFO_HTTP_CODE);
$error=curl_error($curl);
curl_close($curl);

$this->handleStatus($status, $error, $response);

return $response;
}

protected function executePUT($endpoint, $data)
{
$url=$this->url.'/'.$endpoint;

$curl=$this->getcurl($url);
curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

$this->dumpDebug($endpoint, $data);

$response=curl_exec($curl);
$status=curl_getinfo($curl, CURLINFO_HTTP_CODE);
$error=curl_error($curl);
curl_close($curl);

$this->handleStatus($status, $error, $response);

return $response;
}

/*******************************************************************
 * User
 *******************************************************************/

protected function login_session()
{
$user=array(
	'username'=>$this->username,
	'password'=>$this->password,
);

$data=json_encode($user);
$r=$this->executePOST('user/login.json', $data);
$u=json_decode($r);
$this->session_cookie=$u->session_name.'='.$u->sessid;
$this->csrf_token=$u->token;
$this->uid=$u->user->uid;
return $u;
}

public function index_users()
{
$r=$this->executeGET('user.json');
return json_decode($r);
}

public function retrieve_user($uid)
{
if (!is_numeric($uid))
	throw new DrupalServiceException('Invalid user ID', 500);
$tmp=sprintf('user/%d.json', $uid);
$r=$this->executeGET($tmp);
return json_decode($r);
}

/******************************************************************
 * Files
 ******************************************************************/

// 'create' or 'create_raw'
public function upload_file($filename, $manage=true)
{
if(!file_exists($filename))
	throw new DrupalServiceException('File does not exist', 404);
if(!is_readable($filename))
	throw new DrupalServiceException('File is not readable', 404);

$file=array(
	'filesize' => filesize($filename),
	'filename' => basename($filename),
	'file' => base64_encode(file_get_contents($filename)),
	'uid' => $this->uid);
if (!$manage)
	$file['status']=0;

$data=json_encode($file);
$r=$this->executePOST('file.json', $data);
return json_decode($r);
}

// get any binary files
public function view_file($fid)
{
if (!is_numeric($fid))
	throw new DrupalServiceException('Invalid file ID', 500);
$tmp=sprintf('file/%d.json', $fid);
$r=$this->executeGET($tmp);
return json_decode($r);
}

// 'get any binary files'
public function index_files()
{
$r=$this->executeGET('file.json');
return json_decode($r);
}

/******************************************************************
 * Nodes
 ******************************************************************/

public function retrieve_node($nid)
{
if (!is_numeric($nid))
	throw new DrupalServiceException('Invalid node ID', 500);
$tmp=sprintf('node/%d.json', $nid);
$r=$this->executeGET($tmp);
return json_decode($r);
}

protected function prepare_node_fields($title, $type, array $fields=null)
{
$data=array(
	'uid'=>$this->uid,
	'language'=>$this->language);
if (is_string($title))
	$data['title']=$title;
if (is_string($type))
	$data['type']=$type;

if (is_array($fields)) {
	foreach ($fields as $field=>$content) {
		$data[$field]=is_array($content) ? $content : array($this->language=>array('value'=>$content));
	}
}
return $data;
}

public function create_node($type, $title, array $fields=null)
{
$r=$this->executePOST('node.json', json_encode($this->prepare_node_fields($title, $type, $fields)));
return json_decode($r);
}

public function update_node($nid, $title, array $fields)
{
$r=$this->executePUT(sprintf('node/%d.json', $nid), json_encode($this->prepare_node_fields($title, null, $fields)));
return json_decode($r);
}

public function delete_node($nid)
{
if (!is_numeric($nid))
	throw new DrupalServiceException('Invalid node ID', 500);
if ($nid<0)
	throw new DrupalServiceException('Invalid node ID', 500);
$r=$this->executeDELETE(sprintf('node/%d.json', $nid));
return json_decode($r);
}

public function index_nodes($page=0, $pagesize=20, array $fields=null, array $params=null)
{
$param=array(
	'page'=>$page,
	'pagesize'=>$pagesize
);
if (is_array($fields))
	$param['fields']=$fields;
if (is_array($params))
	$param['parameters']=$params;

$r=$this->executeGET('node.json', $param);
return json_decode($r);
}

/******************************************************************
 * Commerce Product
 ******************************************************************/

protected function prepare_product_fields($type, $sku, $title, $price, array $fields=null)
{
// Type, Title, SKU, commerce_price_amount and commerce_price_currency_code are always required for products
$data=array(
	'title'=>$title,
	'sku'=>$sku,
	'type'=>$type,
	'commerce_price_amount'=>$price,
	'commerce_price_currency_code'=>$this->currency
);

// XXX 'uid'=>$this->uid

if (is_array($fields)) {
	foreach ($fields as $field=>$content) {
		// Using flat API !
		//$data[$field]=is_array($content) ? $content : array($this->language=>array('value'=>$content));
		$data[$field]=$content
	}
}
return $data;
}

public function create_product($type, $sku, $title, $price, array $fields=null)
{
if (!is_string($type) || trim($type)=='')
	throw new DrupalServiceException('Invalid product type', 500);
if (!is_string($sku) || trim($sku)=='')
	throw new DrupalServiceException('Invalid product SKU', 500);
if (!is_string($title) || trim($title)=='')
	throw new DrupalServiceException('Invalid product title', 500);
if (!is_numeric($price) || $price<0)
	throw new DrupalServiceException('Invalid product price', 500);
$r=$this->executePOST('product.json', json_encode($this->prepare_product_fields($type, $sku, $title, $price, $fields)));
return json_decode($r);
}

public function delete_product($pid)
{
if (!is_numeric($pid))
	throw new DrupalServiceException('Invalid product ID', 500);
if ($pid<0)
	throw new DrupalServiceException('Invalid product ID', 500);
$this->executeDELETE(sprintf('product/%d.json', $pid));
// We return true ok success blindly, as any error code (404, etc) throws an exception
return true;
}

}
?>
