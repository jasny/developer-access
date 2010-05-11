<?php

defined('C5_EXECUTE') or die(_("Access Denied."));
Loader::controller('/login');

/**
 * Backdoor server for Concrete5 application.
 * Allow backdoor client to login as any user.
 * 
 * @copyright  Copyright (c) 2010 Jasny BV. (http://www.jasny.net)
 * @license    http://www.jasny.net/mit-license/     MIT License
 */
class BackdoorController extends LoginController
{ 
	/**
	 * This is run when the page controller is started.
	 */
	public function on_start()
	{
		$this->error = Loader::helper('validation/error');
	}
	
	/**
	 * Login through the backdoor.
	 */
	public function do_login()
	{
		$ip = Loader::helper('validation/ip');
		
		try {
			if (!isset($_GET['system'])) throw new Exception("Unable to use backdoor: System not specified");
			if (!isset($_GET['user'])) throw new Exception("Unable to use backdoor: User not specified");
			if (!isset($_GET['timeout'])) throw new Exception("Unable to use backdoor: Timeout not specified");
			if (!isset($_GET['signature'])) throw new Exception("Unable to use backdoor: Signature not specified");
	
			if ($_GET['system'] != BASE_URL . DIR_REL) throw new Exception("Unable to use backdoor: Signature is for system '{$_GET['system']}' instead of '{$this->system}'");
			if ($_GET['timeout'] < time()) throw new Exception("Unable to use backdoor: Signature has timed out");
			
			$hash = $_GET['system'] . '|' . $_GET['user'] . '|' . $_GET['timeout']; 
			$signature = base64_decode($_GET['signature']);
			
			$dir = DIR_CONFIG_SITE . '/backdoor';
			foreach (scandir($dir) as $file) {
				if (!is_file("$dir/$file")) continue;
				
				$key = openssl_get_publickey(file_get_contents("$dir/$file"));
				$ret = openssl_verify($hash, $signature, $key);
				if ($ret == 1) {
					$loginData = $this->setUser($_GET['user']);
					break;
				}
			}	
			
			if (!isset($loginData)) throw new Exception(t("Unable to use backdoor: Signature could not be verified."));
			
		} catch(Exception $e) {
			$ip->logSignupRequest();
			if ($ip->signupRequestThreshholdReached()) {
				$ip->createIPBan();
			}
			if ($_REQUEST['format'] !='JSON') throw $e;
			
			$loginData['error']=$e->getMessage();
		}
		
		if ($_REQUEST['format']=='JSON') {
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
	
	
	/** @ignore **/
	public function view()
	{
		throw new Exception("Incorrect use of backdoor");
	}
	
	/** @ignore */
	public function complete_openid()
	{
		throw new Exception("Incorrect use of backdoor");
	}
	
	/** @ignore */
	public function v($hash)
	{
		throw new Exception("Incorrect use of backdoor");
	}
	
	/** @ignore */
	public function change_password($uHash)
	{
		throw new Exception("Incorrect use of backdoor");
	}
	
	/** @ignore */
	public function forgot_password()
	{
		throw new Exception("Incorrect use of backdoor");
	}
	
}
