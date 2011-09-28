<?php
/*
Script Name: iThemes Downloader
Description: This script logs into the iThemes site as it was configured for 2011 and downloads all themes and plugins you have access to.
Version: 0.1
Author: BrianLayman
Author URI: http://thecodecave.com

Notes: 
	To use this script you must provide valid credentials for YOUR subscription.
	Don't abuse this tool. You are downloading the themes under your own login and it is traceable to you.
	If you run this to often I would expect them to terminate your account...
	Note that this script require a directories named themezips and pluginzips in the execution directory.

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/
if ( file_exists( 'ithemesdl_config.php' ) ) include( 'ithemesdl_config.php' );

if ( !defined('IT_USER_NAME' ) ) define( 'IT_USER_NAME', 'YourUserName' );
if ( !defined('IT_USER_PASSWORD' ) ) define( 'IT_USER_PASSWORD', 'AndPassword' );

class IThemesApi {
	// Hold an instance of the class
	private static $m_pInstance;
	private static $curl;
	private static $ckfile;

	// A private constructor; prevents direct creation of object
	private function __construct() 
	{
		$this->ckfile = tempnam("/tmp", "cookie_ithemes_");
		$this->curl = curl_init();	   
	}

	public static function getInstance() 
	{ 
		if (!self::$m_pInstance) 
		{ 
			self::$m_pInstance = new IThemesApi(); 
		} 

		return self::$m_pInstance; 
	} 
	

	function _authorize($login, $password) {
		$url = "http://ithemes.com/member/member.php";
		curl_setopt ($this->curl, CURLOPT_URL, $url);
		curl_setopt ($this->curl, CURLOPT_COOKIEJAR, $this->ckfile);
		curl_setopt($this->curl, CURLOPT_POST, false);
		curl_setopt($this->curl, CURLOPT_POSTFIELDS, "");
		curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, true); 
		curl_setopt($this->curl, CURLOPT_USERAGENT, "botd Mozilla/4.0 (Compatible; IThemes Auth API)");
		$result = curl_exec($this->curl);

		$start = strpos($result, '"login_attempt_id" value="') + 26;
		$end = strpos($result, '"', $start); 
		$login_attempt_id = substr($result, $start, $end - $start); 

		curl_setopt ($this->curl, CURLOPT_URL, $url);
		curl_setopt ($this->curl, CURLOPT_COOKIEJAR, $this->ckfile);
		curl_setopt($this->curl, CURLOPT_POST, true);
		curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, true); 
		curl_setopt($this->curl, CURLOPT_POSTFIELDS, "amember_login=".urlencode($login)."&amember_pass=".urlencode($password)."&login_attempt_id=".urlencode($login_attempt_id));
		curl_setopt($this->curl, CURLOPT_USERAGENT, "botd Mozilla/4.0 (Compatible; IThemes Auth API)");
		$result1 = curl_exec($this->curl);
		return $result1;
	}

	function get_themes($text) {
		$themes = array();
		$end = 0;
		while ($start = strpos($text, "http://ithemes.com/member/downloads/themes", $end)) {;
			$end = strpos($text, '"', $start); 
			$url = substr($text, $start, $end - $start); 
			$themes[] = $url;
		}
		return $themes;
	}
	
	function get_plugins($text) {
		$plugins = array();
		$end = 0;
		while ($start = strpos($text, "http://ithemes.com/member/downloads/plugins", $end)) {;
			$end = strpos($text, '"', $start); 
			$url = substr($text, $start, $end - $start); 
			$plugins[] = $url;
		}
		return $plugins;
	}
	
	function get_theme_file($url) {
		$theFileName =  basename($url); // goes boom if there are parameters
		$fp = fopen (dirname(__FILE__) . '/themezips/' . $theFileName , 'w+');//This is the file where we save the information
		curl_setopt ($this->curl, CURLOPT_URL, $url);
		curl_setopt ($this->curl, CURLOPT_COOKIEJAR, $this->ckfile);
		curl_setopt($this->curl, CURLOPT_POST, false);
		curl_setopt($this->curl, CURLOPT_POSTFIELDS, "");
 		curl_setopt($this->curl, CURLOPT_TIMEOUT, 50);
		curl_setopt($this->curl, CURLOPT_FILE, $fp);
		curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, true);
		curl_exec($this->curl);
		fclose($fp);
	}
	
	function get_plugin_file($url) {
		$theFileName =  basename($url); // goes boom if there are parameters
		$fp = fopen (dirname(__FILE__) . '/pluginzips/' . $theFileName , 'w+');//This is the file where we save the information
		curl_setopt ($this->curl, CURLOPT_URL, $url);
		curl_setopt ($this->curl, CURLOPT_COOKIEJAR, $this->ckfile);
		curl_setopt($this->curl, CURLOPT_POST, false);
		curl_setopt($this->curl, CURLOPT_POSTFIELDS, "");
 		curl_setopt($this->curl, CURLOPT_TIMEOUT, 50);
		curl_setopt($this->curl, CURLOPT_FILE, $fp);
		curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, true);
		curl_exec($this->curl);
		fclose($fp);
		ob_get_clean();
		flush();
		echo $theFileName . " is done<br/>";
	}
}

	$pIThemesAPI = IThemesApi::getinstance();
	$pageInfo = $pIThemesAPI->_authorize( IT_USER_NAME, IT_USER_PASSWORD );
	$themes = $pIThemesAPI->get_themes($pageInfo);
	$plugins = $pIThemesAPI->get_plugins($pageInfo);
	
	foreach ($themes as $themeurl) 
		$pIThemesAPI->get_theme_file($themeurl);
		
	foreach ($plugins as $pluginurl) 
		$pIThemesAPI->get_plugin_file($pluginurl);