<?php

/**
 * Get pass for backdoor login
 * 
 * @copyright  Copyright (c) 2010 Jasny BV. (http://www.jasny.net)
 * @license    http://www.jasny.net/mit-license/     MIT License
 */
class BackdoorKey
{
	/**
	 * Link format.
	 * @var string
	 */
	public $link = "{%url}/backdoor.php?cmd={%cmd}";
	
	
	/**
	 * Redirect to target.
	 * 
	 * @param string $url
	 * @param string $user
	 */
	public function go()
	{
		header('Location: ' . $this->generateLink($_REQUEST['url'], $_REQUEST['user']));
		echo "You are being redirected to {$_REQUEST['url']}.";
		exit();
	}

	/**
	 * Output link to target url backdoor hash and signature.
	 */
	public function getLink()
	{
		echo $this->generateLink($_REQUEST['url'], $_REQUEST['user']);
		exit();
	}
	
	/**
	 * Get link to target url backdoor hash and signature.
	 * 
	 * @param string $url
	 * @param string $user
	 */
	public function generateLink($url=null, $user=null)
	{
		if (!isset($url)) throw new Exception("Unable to get backdoor link: URL not specified");
		if (!isset($user)) throw new Exception("Unable to get backdoor link: User not specified");
				
		$hash = $this->fetchHash($url, $user);
		
		$signature = null;
		$key = openssl_get_privatekey(file_get_contents(dirname(__FILE__) . "/master.key"));
		if (!openssl_sign($hash, $signature, $key)) throw new Exception("Failed to sign backdoor hash");
		
		return str_replace(array('{%url}', '{%cmd}'), array($url, 'login'), $this->link) . (strpos($this->link, '?') !== false ? '&' : '?') . "hash=" . urlencode($hash) . "&signature=" . urlencode(base64_encode($signature));
	}
	
	/**
	 * Get backdoor hash from app.
	 * 
	 * @param string $url
	 * @param string $user
	 */
	protected function fetchHash($url, $user)
	{
		$location = str_replace(array('{%url}', '{%cmd}'), array($url, 'getHash'), $this->link) . (strpos($this->link, '?') !== false ? '&' : '?') . "user=" . urlencode($user);
		$ch = curl_init($location);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		
		$ret = trim(curl_exec($ch));
		$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		if ($status != 200 || substr($ret, 0, 3) != "BD:") throw new Exception("Failed to get backdoor hash from $url (status $status):\n<blockquote>$ret</blockquote>");
		
		return $ret;
	}
}

// Execute controller command
if (realpath($_SERVER["SCRIPT_FILENAME"]) == realpath(__FILE__)) {
    $ctl = new BackdoorKey();
    
    try {
    	$cmd = !empty($_REQUEST['cmd']) ? $_REQUEST['cmd'] : 'go';
    	if (!empty($_REQUEST['link'])) $ctl->link = $_REQUEST['link'];
    	$ret = $ctl->$cmd();
    	
    } catch (Exception $e) {
    	echo $e->getMessage();
    }
}
