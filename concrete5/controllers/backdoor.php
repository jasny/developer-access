<?php

defined('C5_EXECUTE') or die(_("Access Denied."));
Loader::controller('/login');

/**
 * Backdoor for Concrete5 application
 * 
 * @copyright  Copyright (c) 2010 Jasny BV. (http://www.jasny.net)
 * @license    http://www.jasny.net/mit-license/     MIT License
 */
class BackdoorController extends LoginController
{ 
	/**
	 * Secret word.
	 * @var string
	 */
	protected $secret;
	
	/**
	 * This is run when the page controller is started.
	 */
	public function on_start()
	{
		$this->error = Loader::helper('validation/error');
		
		// Get secret word or generate on virst use
		$co = new Config();
		$pkg = Package::getByHandle('backdoor');
		$co->setPackageObject($pkg);
		$this->secret = $co->get('SECRET');
	}
	
	/**
	 * On view
	 */
	public function view()
	{}

	
	/**
	 * Output backdoor login hash.
	 */
	public function getHash()
	{
		try {
			if (empty($_GET['user'])) throw new Exception(t("Unable create backdoor hash: User isn't specified"));
		
			echo $this->generateHash($_GET['user'], time() + 5);
		} catch (Exception $e) {
			header("HTTP/1.0 400 Bad Request");
			echo $e;
		}
		
		die;
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
	 * Alais of do_login. 
	 */
	public function login()
	{
		$this->do_login();
	}
	
	/**
	 * Login through the backdoor.
	 */
	public function do_login()
	{
		try {
			if (!isset($_GET['hash'])) throw new Exception(t("Unable to use backdoor: Hash isn't specified"));
			if (!isset($_GET['signature'])) throw new Exception(t("Unable to use backdoor: Signature isn't specified"));
			
			$hash = $_GET['hash'];
			$signature = base64_decode($_GET['signature']);
		
			$ip = Loader::helper('validation/ip');

			list(, $timeout, $user) = explode('|', $hash, 3);

			if ($hash != $this->generateHash($user, $timeout)) throw new Exception(t("Unable to use backdoor: Checksum of hash didn't match"));
			if ($timeout < time()) throw new Exception(t("Unable to use backdoor: Hash is no longer valid"));
			
			$dir = DIR_CONFIG_SITE . '/backdoor';
			foreach (scandir($dir) as $file) {
				if (!is_file("$dir/$file")) continue;
				
				$key = openssl_get_publickey(file_get_contents("$dir/$file"));
				$ret = openssl_verify($hash, $signature, $key);
				if ($ret == 1) {
					$loginData = $this->setUser($user);
					break;
				}
			}	
			
			if (!isset($loginData)) throw new Exception(t("Unable to use backdoor: Signature could not be verified."));
			
		} catch(Exception $e) {
			$ip->logSignupRequest();
			if ($ip->signupRequestThreshholdReached()) {
				$ip->createIPBan();
			}
			if( $_REQUEST['format'] !='JSON') throw $e;
			
			$loginData['error']=$e->getMessage();
		}
		
		if( $_REQUEST['format']=='JSON' ){
			$jsonHelper=Loader::helper('json'); 
			echo $jsonHelper->encode($loginData);
			die;
		}	
	}
	
	/**
	 * Set specified user a logged in user.
	 * 
	 * @param string $user
	 */
	protected function setUser($user)
	{
		if (ctype_digit($user)) {
			$uid = $user;
		} else {
			$ui = UserInfo::getByUserName($user);
			if (empty($ui)) throw new Exception(sprintf(t("Unable to use backdoor: User '%s' does not exist"), $user));
			$uid = $ui->getUserId();
		}
		
		$u = User::loginByUserID($uid);
		
		$loginData['success']=1;
		$loginData['msg']=t('Login Successful');	
		$loginData['uID'] = intval($u->getUserID());		
		
		$loginData = $this->finishLogin($loginData);
		return $loginData;
	}
	
	
	/** @ignore */
	public function complete_openid()
	{}
	
	/** @ignore */
	public function v($hash)
	{}
	
	/** @ignore */
	public function change_password($uHash)
	{}
	/** @ignore */
	public function forgot_password()
	{}
	
}
