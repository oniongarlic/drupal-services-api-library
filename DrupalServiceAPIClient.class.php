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

class DrupalServiceAPIClient
{
// API url
protected $url;
protected $debug=false;
protected $uid=1;

// Basic auth username and password
protected $auth=0;
protected $username;
protected $password;
private $session_cookie=null;
private $csrf_token;

// API key auth (WIP)
protected $apikey;

function __construct($url)
{
$this->url=$url;
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

$options=array(
	CURLOPT_HEADER => FALSE,
	CURLOPT_RETURNTRANSFER => TRUE,
	CURLINFO_HEADER_OUT => TRUE,
	CURLOPT_HTTPHEADER => array( 'Content-Type: application/json'));
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

protected function executeGET($endpoint)
{
$url=$this->url.'/'.$endpoint;
$curl=$this->getcurl($url);
curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');

$this->dumpDebug($endpoint);

$response=curl_exec($curl);
$status=curl_getinfo($curl, CURLINFO_HTTP_CODE);

if ($status===0)
	throw new DrupalServiceException('CURL Error: '.curl_error($curl));
if ($status!==200)
	throw new DrupalServiceException('Error', $status);

curl_close($curl);
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

if ($status===0)
	throw new DrupalServiceException('CURL Error: '.curl_error($curl));
if ($status!==200)
	throw new DrupalServiceException('Error: ', $status);

curl_close($curl);
return $response;
}

/**
 * User
 */
 
protected function login_session()
{
$user=array(
	'username'=>$this->username,
	'password'=>$this->password,
);

$data=json_encode($user);
$r=$this->executePOST('user/login.json', $data);
print_r($r);
$this->session_cookie=$r->session_name.'='.$r->sessid;
return json_decode($r);
}

/**
 * Files
 */

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

public function retrieve_node($nid)
{
if (!is_numeric($nid))
	throw new DrupalServiceException('Invalid node ID', 500);
$tmp=sprintf('node/%d.json', $nid);
$r=$this->executeGET($tmp);
return json_decode($r);
}

public function create_node($type, $title, array $fields=null)
{
$data=array(
	'title'=>$title,
	'type'=>$type,
	'language'=>DRUPAL_LANGUAGE_NONE);	
if (is_array($fields)) {
	foreach ($fields as $field=>$content) {
		$data[$field]=is_array($content) ? $content : array(DRUPAL_LANGUAGE_NONE=>array('value'=>$content));
	}
}
$json=json_encode($data);
$r=$this->executePOST('node.json', $json);
return json_decode($r);
}

public function update_node($nid, array $fields)
{
}

public function delete_node($nid)
{
if (!is_numeric($nid))
	throw new DrupalServiceException('Invalid node ID', 500);
return false;
}

public function index_nodes()
{
$r=$this->executeGET('node.json');
return json_decode($r);
}

}
?>
