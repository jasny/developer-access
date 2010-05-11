<?php

/**
 * Backdoor server.
 * Allow backdoor client to login as any user.
 * 
 * @copyright  Copyright (c) 2010 Jasny BV. (http://www.jasny.net)
 * @license    http://www.jasny.net/mit-license/     MIT License
 */
class Backdoor
{
	/**
	 * System name.
	 * @var string
	 */
	public $system;
	
	/**
	 * Login through the backdoor.
	 */
	public function login()
	{
		if (!isset($_GET['system'])) throw new Exception("Unable to use backdoor: System not specified");
		if (!isset($_GET['user'])) throw new Exception("Unable to use backdoor: User not specified");
		if (!isset($_GET['timeout'])) throw new Exception("Unable to use backdoor: Timeout not specified");
		if (!isset($_GET['signature'])) throw new Exception("Unable to use backdoor: Signature not specified");

		if ($_GET['system'] != $this->system) throw new Exception("Unable to use backdoor: Signature is for system '{$_GET['system']}' instead of '{$this->system}'");
		if ($_GET['timeout'] < time()) throw new Exception("Unable to use backdoor: Signature has timed out");
		
		$hash = $_GET['system'] . '|' . $_GET['user'] . '|' . $_GET['timeout']; 
		$signature = base64_decode($_GET['signature']);
		
		$dir = dirname(__FILE__) . '/pubkeys';
		foreach (scandir($dir) as $file) {
			if (!is_file("$dir/$file")) continue;
			
			$key = openssl_get_publickey(file_get_contents("$dir/$file"));
			$ret = openssl_verify($hash, $signature, $key);
			if ($ret == 1) {
				$this->setUser($_GET['user']);
				return;
			}
		}	
		
		throw new Exception("Unable to use backdoor: Signature could not be verified.");
	}
	
	/**
	 * Set specified user a logged in user.
	 * 
	 * @param string $user
	 */
	protected function setUser($user)
	{
		session_start();
		$_SESSION['user'] = $user;
		header('Location: ' . dirname($_SERVER['REQUEST_URI']) . '/index.php');
		exit();
	}
}

// Determining the system based on HTTP_HOST is not very secure, please set this manually.
$system = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . (dirname($_SERVER['REQUEST_URI']) == '/' ? '' : dirname($_SERVER['REQUEST_URI']));

// Execute controller command
if (realpath($_SERVER["SCRIPT_FILENAME"]) == realpath(__FILE__)) {
    $ctl = new Backdoor();
    
    try {
    	$ctl->system = $system;
    	$ctl->login();
    } catch (Exception $e) {
    	header("HTTP/1.0 400 Bad Request");
    	echo $e->getMessage();
    }
}
