<?php

defined('C5_EXECUTE') or die(_("Access Denied."));

/**
 * Installation controller for backdoor package.
 * 
 * @copyright  Copyright (c) 2010 Jasny BV. (http://www.jasny.net)
 * @license    http://www.jasny.net/mit-license/     MIT License
 */
class PubkeyPackage extends Package
{
	protected $pkgHandle = 'pubkey';
	protected $appVersionRequired = '5.3.3';
	protected $pkgVersion = '1.0';

	/**
	 * Return package description.
	 * 
	 * @return string
	 */
	public function getPackageDescription()
	{
		return t("Login as any user using a public key");
	}

	/**
	 * Return package name.
	 * 
	 * @return string
	 */
	public function getPackageName()
	{
		return "Public Key Authentication";
	}

	/**
	 * Install package
	 */
	public function install()
	{
		$pkg = parent::install();
		                
		mkdir(DIR_CONFIG_SITE . '/pubkeys');
		
		Loader::model('single_page');
		$d = SinglePage::add('/pubkey', $pkg);
		$d->update(array('cFilename'=>"/pubkey.php"));
	}
}
