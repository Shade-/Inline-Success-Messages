<?php
/**
 * Inline Success Messages
 * 
 * Adds support for inline success messages instead of redirection.
 *
 * @package Inline Success Messages
 * @author  Shade <legend_k@live.it>
 * @license http://www.gnu.org/licenses/ GNU/GPL license
 * @version 1.0
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
		'website' => '',
		'author' => 'Shade',
		'authorsite' => '',
		'version' => '1.0',
		'compatibility' => '16*',
		'guid' => 'f6f2925d440239e6f3a894703ba088c6'
	);
}

function inlinesuccess_is_installed()
{
	global $cache;
	
	$info = inlinesuccess_info();
	$installed = $cache->read("shade_plugins");
	if ($installed[$info['name']]) {
		return true;
	}
}

function inlinesuccess_install()
{
	global $db, $mybb, $cache, $PL, $lang;
	
	if (!file_exists(PLUGINLIBRARY)) {
		flash_message("The selected plugin could not be installed because <a href=\"http://mods.mybb.com/view/pluginlibrary\">PluginLibrary</a> is missing.", "error");
		admin_redirect("index.php?module=config-plugins");
	}
		
	if (!$lang->inlinesuccess) {
		$lang->load('inlinesuccess');
	}
	
	$info = inlinesuccess_info();
	$shadePlugins = $cache->read('shade_plugins');
	$shadePlugins[$info['name']] = array(
		'title' => $info['name'],
		'version' => $info['version']
	);
	$cache->update('shade_plugins', $shadePlugins);
	
	$PL or require_once PLUGINLIBRARY;
	
	$PL->edit_core('inlinesuccess', 'inc/functions.php', array(
		// redirect() function patch
		array(
			'search' => '$plugins->run_hooks("redirect", $redirect_args);',
			'after' => 'if($mybb->settings[\'inlinesuccess_enabled\']) {
	global $errors, $avatar_error;
	$action = $mybb->input[\'action\'];
	$find = "do_";
	if(strpos($action, $find) !== FALSE) $mybb->input[\'action\'] = str_replace($find, "", $action);
	$errors = $avatar_error = array("success" => 1,
		"message" => $message
	);
	$errors = inline_success($errors);
	return $errors;
}'
		),
		// inline_error() function patch
		array(
			'search' => 'foreach($errors as $error)',
			'before' => 'if($mybb->settings[\'inlinesuccess_enabled\']) {
	global $check;
	if(!empty($errors[\'success\'])) {
		if(empty($check)) {
			$errors = inline_success($errors);
		}
		return $errors;
	}
}'
		),
	), true);
	
	// Euantor's templating system	   
	$dir = new DirectoryIterator(dirname(__FILE__) . '/InlineSuccess/templates');
	$templates = array();
	foreach ($dir as $file) {
		if (!$file->isDot() AND !$file->isDir() AND pathinfo($file->getFilename(), PATHINFO_EXTENSION) == 'html') {
			$templates[$file->getBasename('.html')] = file_get_contents($file->getPathName());
		}
	}
	
	$PL->templates('inlinesuccess', 'Inline Success Messages', $templates);
	
	$PL->settings('inlinesuccess', $lang->inlinesuccess_settings, $lang->inlinesuccess_settings_desc, array(
		'enabled' => array(
			'title' => $lang->inlinesuccess_settings_enable,
			'description' => $lang->inlinesuccess_settings_enable_desc,
			'value' => '1'
		),
	));
	
}

function inlinesuccess_uninstall()
{
	global $db, $cache, $PL;
	
	if (!file_exists(PLUGINLIBRARY)) {
		flash_message("The selected plugin could not be uninstalled because <a href=\"http://mods.mybb.com/view/pluginlibrary\">PluginLibrary</a> is missing.", "error");
		admin_redirect("index.php?module=config-plugins");
	}
	
	$PL or require_once PLUGINLIBRARY;
	
	$info = inlinesuccess_info();
	// delete the plugin from cache
	$shadePlugins = $cache->read('shade_plugins');
	unset($shadePlugins[$info['name']]);
	$cache->update('shade_plugins', $shadePlugins);
	
	// oh dear, core edits are passing away!
	$PL->edit_core('inlinesuccess', 'inc/functions.php', array(), true);
	
	$PL->templates_delete('inlinesuccess');	
	$PL->settings_delete('inlinesuccess');
}

/**
 * Throws a success message instead of a "un"friendly redirect
 *
 * @param mixed An array or string containing the messages to push to the DOM
 * @return string The generated code
 */

function inline_success($messages)
{
	global $theme, $mybb, $db, $lang, $templates, $check, $errors;
	
	if (!is_array($messages)) {
		$messages = array(
			$messages
		);
	}
	
	// populating this global variable to ensure no inline success messages inside error messages
	$check = 1;
	
	foreach ($messages as $message) {
		$messagelist .= $message . "\n";
	}
	
	eval("\$errors = \"" . $templates->get("inlinesuccess_success_inline") . "\";");
	
	return $errors;
}