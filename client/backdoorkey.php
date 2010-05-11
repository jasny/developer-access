<?php

/**
 * Secure backdoor client.
 * Login to an application as any user.
 * 
 * @copyright  Copyright (c) 2010 Jasny BV. (http://www.jasny.net)
 * @license    http://www.jasny.net/mit-license/     MIT License
 */
class BackdoorClient
{
	/**
	 * Link format.
	 * @var string
	 */
	public $link = "{%system}/backdoor.php";
	
	
	/**
	 * Redirect to target.
	 * 
	 * @param string $url
	 * @param string $user
	 */
	public function go()
	{
		$link = $this->generateLink($_REQUEST['system'], $_REQUEST['user']);
		
		header("Location: $link");
		echo "You are being redirected to <a href='$link'>{$_REQUEST['system']}</a>.";
		exit();
	}

	/**
	 * Output link to target url backdoor hash and signature.
	 */
	public function getLink()
	{
		echo $this->generateLink($_REQUEST['system'], $_REQUEST['user']);
		exit();
	}
	
	/**
	 * Get link to target url backdoor hash and signature.
	 * 
	 * @param string $url
	 * @param string $user
	 */
	public function generateLink($system=null, $user=null)
	{
		if (!isset($system)) throw new Exception("Unable to get backdoor link: System not specified");
		if (!isset($user)) throw new Exception("Unable to get backdoor link: User not specified");
		
		$timeout = time() + 5;
		$hash = "$system|$user|$timeout";
		
		$signature = null;
		$key = openssl_get_privatekey(file_get_contents(dirname(__FILE__) . "/master.key"));
		if (!openssl_sign($hash, $signature, $key)) throw new Exception("Failed to sign backdoor hash");
		
		return str_replace(array('{%system}'), array($system, 'login'), $this->link) . (strpos($this->link, '?') !== false ? '&' : '?') . "system=" . urlencode($system) . "&user=" . urlencode($user) . "&timeout=" . urlencode($timeout) . "&signature=" . urlencode(base64_encode($signature));
	}
}

// Execute controller command
if (realpath($_SERVER["SCRIPT_FILENAME"]) == realpath(__FILE__)) {
    $ctl = new BackdoorClient();
    
    try {
    	$cmd = !empty($_REQUEST['cmd']) ? $_REQUEST['cmd'] : 'go';
    	if (!empty($_REQUEST['link'])) $ctl->link = $_REQUEST['link'];
    	$ctl->$cmd();
    	
    } catch (Exception $e) {
    	echo $e->getMessage();
    }
}
