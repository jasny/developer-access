<?php

/**
 * Backdoor login
 */
class Backdoor
{
	/**
	 * Secret word.
	 * @var string
	 */
	protected $secret = "Som3thing V3RY s3cr3t!";

	
	/**
	 * Output backdoor login hash.
	 */
	public function getHash()
	{
		if (empty($_GET['user'])) throw new Exception(t("Unable create backdoor hash: User isn't specified"));
		echo $this->generateHash($_GET['user'], time() + 5);
	}
	
	/**
	 * Get backdoor login hash.
	 * 
	 * @param string $user     Username
	 * @param string $timeout  Timestamp
	 * @return string
	 */
	protected function generateHash($user, $timeout)
	{
		return "BD:" . md5($user . $timeout . $this->secret) . '|' . $timeout . '|' . $user;
	}
	
	/**
	 * Login through the backdoor.
	 * 
	 * @param string $hash       Backdoor hash
	 * @param string $signature  Base64 SSL signature of hash
	 */
	public function login($hash=null, $signature=null)
	{
		if (!isset($hash) && isset($_GET['hash'])) $hash = $_GET['hash'];
		if (!isset($signature) && isset($_GET['signature'])) $signature = $_GET['signature'];
		
		if (!isset($hash)) throw new Exception("Unable to login: Hash not specified");
		if (!isset($signature)) throw new Exception("Unable to login: Signature not specified");
		
		$signature = base64_decode($signature);
		
		list(, $timeout, $user) = explode('|', $hash, 3);
		if ($hash != $this->generateHash($user, $timeout)) throw new Exception("Unable to login: Checksum of hash didn't match");
		if ($timeout < time()) throw new Exception("Unable to login: Hash is no longer valid");
		
		$dir = dirname(__FILE__) . '/pubkeys';
		foreach (scandir($dir) as $file) {
			if (!is_file("$dir/$file")) continue;
			
			$key = openssl_get_publickey(file_get_contents("$dir/$file"));
			$ret = openssl_verify($hash, $signature, $key);
			if ($ret == 1) {
				$this->setUser($user);
				return;
			}
		}	
		
		throw new Exception("Signature could not be verified.");
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
		header('Location: ' . $_SERVER['host'] . dirname($_SERVER['REQUEST_URI']) . '/index.php');
		exit();
	}
}

// Execute controller command
if (realpath($_SERVER["SCRIPT_FILENAME"]) == realpath(__FILE__) && isset($_GET['cmd'])) {
    $ctl = new Backdoor();
    
    try {
    	$ret = $ctl->$_GET['cmd']();
    } catch (Exception $e) {
    	header("HTTP/1.0 400 Bad Request");
    	echo $e->getMessage();
    }
}
