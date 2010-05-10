<?php

defined('C5_EXECUTE') or die(_("Access Denied."));

/**
 * Installation controller for backdoor package
 * 
 * @copyright  Copyright (c) 2010 Jasny BV. (http://www.jasny.net)
 * @license    http://www.jasny.net/mit-license/     MIT License
 */
class BackdoorPackage extends Package
{
	protected $pkgHandle = 'backdoor';
	protected $appVersionRequired = '5.3.3';
	protected $pkgVersion = '1.0';

	/**
	 * Return package description.
	 * 
	 * @return string
	 */
	public function getPackageDescription()
	{
		return t("Login as any user through a secure backdoor");
	}

	/**
	 * Return package name.
	 * 
	 * @return string
	 */
	public function getPackageName()
	{
		return "Backdoor";
	}

	/**
	 * Install package
	 */
	public function install()
	{
		$pkg = parent::install();
		                
		$id = Loader::helper('validation/identifier');
		$pkg->saveConfig('SECRET', $id->getString());
		
		mkdir(DIR_CONFIG_SITE . '/backdoor');
		
		Loader::model('single_page');
		$d = SinglePage::add('/backdoor', $pkg);
		$d->update(array('cFilename'=>"/backdoor.php"));
	}
}
