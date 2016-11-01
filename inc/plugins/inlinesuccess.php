<?php
/**
 * Inline Success Messages
 * 
 * Adds support for inline success messages instead of redirection pages.
 *
 * @package Inline Success Messages
 * @author  Shade <legend_k@live.it>
 * @license http://www.gnu.org/licenses/ GNU/GPL license
 * @version 2.1
 */

if (!defined('IN_MYBB')) {
	die('Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.');
}

if (!defined("PLUGINLIBRARY")) {
	define("PLUGINLIBRARY", MYBB_ROOT . "inc/plugins/pluginlibrary.php");
}

function inlinesuccess_info()
{
	return array(
		'name' => 'Inline Success Messages',
		'description' => 'Adds support for inline success messages globally instead of an (un)friendly redirection page.',
		'website' => 'https://www.mybboost.com/forum-inline-success-messages',
		'author' => 'Shade',
		'version' => '2.1',
		'compatibility' => '18*'
	);
}

function inlinesuccess_is_installed()
{
	global $cache;
	
	$info      = inlinesuccess_info();
	$installed = $cache->read("shade_plugins");
	if ($installed[$info['name']]) {
		return true;
	}
}

function inlinesuccess_install()
{
	global $db, $mybb, $cache, $PL, $lang;
	
	$lang->load('inlinesuccess');
	
	if (!file_exists(PLUGINLIBRARY)) {
		flash_message($lang->inlinesuccess_missing_pl, "error");
		admin_redirect("index.php?module=config-plugins");
	}
	
	// Add the plugin to our cache
	$info                        = inlinesuccess_info();
	$shadePlugins                = $cache->read('shade_plugins');
	$shadePlugins[$info['name']] = array(
		'title' => $info['name'],
		'version' => $info['version']
	);
	$cache->update('shade_plugins', $shadePlugins);
	
	$PL or require_once PLUGINLIBRARY;
	
	// Add templates	   
	$dir       = new DirectoryIterator(dirname(__FILE__) . '/InlineSuccess/templates');
	$templates = array();
	foreach ($dir as $file) {
		if (!$file->isDot() AND !$file->isDir() AND pathinfo($file->getFilename(), PATHINFO_EXTENSION) == 'html') {
			$templates[$file->getBasename('.html')] = file_get_contents($file->getPathName());
		}
	}
	$PL->templates('inlinesuccess', 'Inline Success Messages', $templates);
	
	// Add settings
	$PL->settings('inlinesuccess', $lang->inlinesuccess_settings, $lang->inlinesuccess_settings_desc, array(
		'force' => array(
			'title' => $lang->inlinesuccess_settings_force,
			'description' => $lang->inlinesuccess_settings_force_desc,
			'value' => 0
		)
	));
	
}

function inlinesuccess_uninstall()
{
	global $db, $cache, $PL, $lang;
	
	$lang->load('inlinesuccess');
	
	if (!file_exists(PLUGINLIBRARY)) {
		flash_message("The selected plugin could not be uninstalled because <a href=\"http://mods.mybb.com/view/pluginlibrary\">PluginLibrary</a> is missing.", "error");
		admin_redirect("index.php?module=config-plugins");
	}
	
	$PL or require_once PLUGINLIBRARY;
	
	// Delete from cache
	$info         = inlinesuccess_info();
	$shadePlugins = $cache->read('shade_plugins');
	unset($shadePlugins[$info['name']]);
	$cache->update('shade_plugins', $shadePlugins);
	
	$PL->templates_delete('inlinesuccess');
	$PL->settings_delete('inlinesuccess');
	
}

$plugins->add_hook("redirect", "inlinesuccess_redirect");
$plugins->add_hook("pre_output_page", "inlinesuccess_show_message");
$plugins->add_hook("usercp_start", "inlinesuccess_overwrite_lang");
$plugins->add_hook("private_start", "inlinesuccess_overwrite_lang");
$plugins->add_hook("member_do_login_start", "inlinesuccess_overwrite_lang");
$plugins->add_hook("member_logout_start", "inlinesuccess_overwrite_lang");

if (defined('IN_ADMINCP')) {	
	$plugins->add_hook("admin_load", "inlinesuccess_ad");
}

// Advertising
function inlinesuccess_ad()
{
	global $cache, $mybb;
	
	$info = inlinesuccess_info();
	$plugins = $cache->read('shade_plugins');
	
	if (!in_array($mybb->user['uid'], (array) $plugins[$info['name']]['ad_shown'])) {
		
		flash_message('Thank you for using ' . $info['name'] . '! You might also be interested in other great plugins on <a href="http://projectxmybb.altervista.org">MyBBoost</a>, where you can also get support for ' . $info['name'] . ' itself.<br /><small>This message will not be shown again to you.</small>', 'success');
		
		$plugins[$info['name']]['ad_shown'][] = $mybb->user['uid'];
		$cache->update('shade_plugins', $plugins);
		
	}
	
}

// Save message in the session
function inlinesuccess_redirect(&$args)
{
	global $mybb, $validated, $loginhandler;
	
	if ((($mybb->user['showredirect'] or !$mybb->user['uid']) and !$mybb->settings['inlinesuccess_force'] and $loginhandler->captcha_verified != true) or $mybb->input['ajax']) {
		return false;
	}
	
	if ($mybb->settings['inlinesuccess_force']) {
		$mybb->user['showredirect'] = 0; // Should bypass the redirection page
	}
	
	// When logging in, the user uid is 0, so the bypass does not work. We need to temporarily assign a uid to it
	if ($validated and $loginhandler->captcha_verified == true) {
		$mybb->user['uid'] = random_str();
	}
	
	if (!session_id()) {
		session_start();
	}
	
	$_SESSION['inlinesuccess'] = array(
		'message' => $args['message']
	);
}

// Populates the $success variable
function inlinesuccess_show_message(&$contents)
{
	global $mybb, $templates;
	
	if (!session_id()) {
		session_start();
	}
	
	// Got a message to show
	if ($_SESSION['inlinesuccess']) {
	
		$message = addslashes($_SESSION['inlinesuccess']['message']);
		eval("\$html = \"" . $templates->get("inlinesuccess_success") . "\";");
		
		// Aaaand we're done here
		unset($_SESSION['inlinesuccess']);
		
		$contents = str_replace('</body>', $html . '</body>', $contents);
		
	}
	
	return $contents;
}

function inlinesuccess_overwrite_lang()
{
	$GLOBALS['lang']->load('inlinesuccess');
}