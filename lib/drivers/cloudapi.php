<?php 


function vacation_read(array &$data) {
	$rcmail = rcmail::get_instance();
	
	$cldClient = new RestRequest($rcmail->config->get("vacation_endpoint")."/".$rcmail->user->get_username(), "GET");
	$cldClient->setUsername($rcmail->config->get("cloudapi_username"));
	$cldClient->setPassword($rcmail->config->get("cloudapi_password"));
	
	$cldClient->execute();

/*
 * 
 *  - username : string, username or email (get).
 - email : string, email of the user if username is a full email (get/set).
 - email_local : string, email local part if username is a full email (get/set).
 - email_domain : string, email domain if username is a full email (get/set).
 - vacation_enable : boolean, flag to enable/disable the vacation message
   (get/set).
 - vacation_start : integer, timestamp/date of the vacation start (get/set)
 - vacation_end : integer, timestamp/date of the vacation end (get/set)
 - vacation_subject : string, subject of the vacation message (get/set).
 - vacation_message : string, message of the vacation (get/set).
 - vacation_keepcopyininbox, boolean, flag to enable/disable the vacation keep
   copy in inbox flag (get/set).
 - vacation_forwarder : string, forward address of the vacation (get/set).
 * 
 */

	//$responseBody = $cldClient->getResponseBody();
	//$data['vacation_subject'] = $responseBody['vacation_subject'];
	$data  = json_decode($cldClient->getResponseBody(), true);
	
	return PLUGIN_SUCCESS;
}
	function log_error($code, $message) {
		rcmail::raise_error(array(
		'code' => $code, 'type' => 'php',
		'file' => __FILE__, 'line' => __LINE__,
		'message' => $message),
		true, true);
	}


function vacation_write(array &$data) {
	$rcmail = rcmail::get_instance();
	
	$cldClient = new RestRequest($rcmail->config->get("vacation_endpoint")."/".$rcmail->user->get_username(), "PUT", $data);
	$cldClient->setUsername($rcmail->config->get("cloudapi_username"));
	$cldClient->setPassword($rcmail->config->get("cloudapi_password"));
	
	$cldClient->execute();

//	log_error(600, json_encode($cldClient->getResponseBody()));

	return PLUGIN_SUCCESS;
}
/*
class RestRequest
{
	protected $url;
	protected $verb;
	protected $requestBody;
	protected $requestLength;
	protected $username;
	protected $password;
	protected $acceptType;
	protected $responseBody;
	protected $responseInfo;

	public function __construct ($url = null, $verb = 'GET', $requestBody = null)
	{
		$this->url				= $url;
		$this->verb				= $verb;
		$this->requestBody		= $requestBody;
		$this->requestLength	= 0;
		$this->username			= null;
		$this->password			= null;
		$this->acceptType		= 'application/json';
		$this->responseBody		= null;
		$this->responseInfo		= null;

		if ($this->requestBody !== null)
		{
			$this->buildPostBody();
		}
	}

	public function flush ()
	{
		$this->requestBody		= null;
		$this->requestLength	= 0;
		$this->verb				= 'GET';
		$this->responseBody		= null;
		$this->responseInfo		= null;
	}

	public function execute ()
	{
		$ch = curl_init();
		$this->setAuth($ch);

		try
		{
			switch (strtoupper($this->verb))
			{
				case 'GET':
					$this->executeGet($ch);
					break;
				case 'POST':
					$this->executePost($ch);
					break;
				case 'PUT':
					$this->executePut($ch);
					break;
				case 'DELETE':
					$this->executeDelete($ch);
					break;
				default:
					throw new InvalidArgumentException('Current verb (' . $this->verb . ') is an invalid REST verb.');
			}
		}
		catch (InvalidArgumentException $e)
		{
			curl_close($ch);
			throw $e;
		}
		catch (Exception $e)
		{
			curl_close($ch);
			throw $e;
		}

	}

	public function buildPostBody ($data = null)
	{
		$data = ($data !== null) ? $data : $this->requestBody;

		if (!is_array($data))
		{
			throw new InvalidArgumentException('Invalid data input for postBody.  Array expected');
		}

		$data = http_build_query($data, '', '&');
		$this->requestBody = $data;
	}

	protected function executeGet ($ch)
	{
		$this->doExecute($ch);
	}

	protected function executePost ($ch)
	{
		if (!is_string($this->requestBody))
		{
			$this->buildPostBody();
		}

		curl_setopt($ch, CURLOPT_POSTFIELDS, $this->requestBody);
		curl_setopt($ch, CURLOPT_POST, 1);

		$this->doExecute($ch);
	}

	protected function executePut ($ch)
	{
		if (!is_string($this->requestBody))
		{
			$this->buildPostBody();
		}

		$this->requestLength = strlen($this->requestBody);

		$fh = fopen('php://memory', 'rw');
		fwrite($fh, $this->requestBody);
		rewind($fh);

		curl_setopt($ch, CURLOPT_INFILE, $fh);
		curl_setopt($ch, CURLOPT_INFILESIZE, $this->requestLength);
		curl_setopt($ch, CURLOPT_PUT, true);

		$this->doExecute($ch);

		fclose($fh);
	}

	protected function executeDelete ($ch)
	{
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');

		$this->doExecute($ch);
	}

	protected function doExecute (&$curlHandle)
	{
		$this->setCurlOpts($curlHandle);
		$this->responseBody = curl_exec($curlHandle);
		$this->responseInfo	= curl_getinfo($curlHandle);

		curl_close($curlHandle);
	}

	protected function setCurlOpts (&$curlHandle)
	{
		curl_setopt($curlHandle, CURLOPT_TIMEOUT, 10);
		curl_setopt($curlHandle, CURLOPT_URL, $this->url);
		curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curlHandle, CURLOPT_HTTPHEADER, array ('Accept: ' . $this->acceptType));
	}

	protected function setAuth (&$curlHandle)
	{
		if ($this->username !== null && $this->password !== null)
		{
			curl_setopt($curlHandle, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
			curl_setopt($curlHandle, CURLOPT_USERPWD, $this->username . ':' . $this->password);
		}
	}

	public function getAcceptType ()
	{
		return $this->acceptType;
	}

	public function setAcceptType ($acceptType)
	{
		$this->acceptType = $acceptType;
	}

	public function getPassword ()
	{
		return $this->password;
	}

	public function setPassword ($password)
	{
		$this->password = $password;
	}

	public function getResponseBody ()
	{
		return $this->responseBody;
	}

	public function getResponseInfo ()
	{
		return $this->responseInfo;
	}

	public function getUrl ()
	{
		return $this->url;
	}

	public function setUrl ($url)
	{
		$this->url = $url;
	}

	public function getUsername ()
	{
		return $this->username;
	}

	public function setUsername ($username)
	{
		$this->username = $username;
	}

	public function getVerb ()
	{
		return $this->verb;
	}

	public function setVerb ($verb)
	{
		$this->verb = $verb;
	}
}

*/
?>
