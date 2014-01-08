<?php
/**
 * Inline Success Messages
 * 
 * Adds support for inline success messages instead of redirection pages.
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
		'website' => 'https://github.com/Shade-/Inline-Success-Messages',
		'author' => 'Shade',
		'version' => '1.0.2',
		'compatibility' => '16*',
		'guid' => 'f6f2925d440239e6f3a894703ba088c6'
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
	
	if (!file_exists(PLUGINLIBRARY)) {
		flash_message("The selected plugin could not be installed because <a href=\"http://mods.mybb.com/view/pluginlibrary\">PluginLibrary</a> is missing.", "error");
		admin_redirect("index.php?module=config-plugins");
	}
	
	if (!$lang->inlinesuccess) {
		$lang->load('inlinesuccess');
	}
	
	// add the plugin to our cache
	$info                        = inlinesuccess_info();
	$shadePlugins                = $cache->read('shade_plugins');
	$shadePlugins[$info['name']] = array(
		'title' => $info['name'],
		'version' => $info['version']
	);
	$cache->update('shade_plugins', $shadePlugins);
	
	$PL or require_once PLUGINLIBRARY;
	
	// add templates	   
	$dir       = new DirectoryIterator(dirname(__FILE__) . '/InlineSuccess/templates');
	$templates = array();
	foreach ($dir as $file) {
		if (!$file->isDot() AND !$file->isDir() AND pathinfo($file->getFilename(), PATHINFO_EXTENSION) == 'html') {
			$templates[$file->getBasename('.html')] = file_get_contents($file->getPathName());
		}
	}
	$PL->templates('inlinesuccess', 'Inline Success Messages', $templates);
	
	// add settings
	$PL->settings('inlinesuccess', $lang->inlinesuccess_settings, $lang->inlinesuccess_settings_desc, array(
		'enabled' => array(
			'title' => $lang->inlinesuccess_settings_enable,
			'description' => $lang->inlinesuccess_settings_enable_desc,
			'value' => 1
		),
		'force' => array(
			'title' => $lang->inlinesuccess_settings_force,
			'description' => $lang->inlinesuccess_settings_force_desc,
			'value' => 0
		)
	));
	
	// add $inlinesuccess variable to...
	// all templates
	find_replace_multitemplatesets('(\{\$([^->]*)error(.*)\})', '$0 {$inlinesuccess}');
	
	require_once MYBB_ROOT."/inc/adminfunctions_templates.php";
	// private
	$private = array('private', 'private_empty', 'private_folders', 'private_tracking');
	foreach ($private as $title) {
		find_replace_templatesets($title, '#(\{\\$usercpnav\}(?:\n|.)*\<td valign=\"top\"\>)#i', '$0 {$inlinesuccess}');		
	}
	// modcp
	$modcp = array('modcp_finduser', 'modcp_announcements', 'modcp_reports', 'modcp_banning');
	foreach ($modcp as $title) {
		find_replace_templatesets($title, '#(\{\\$modcp_nav\}(?:\n|.)*\<td valign=\"top\"\>)#i', '$0 {$inlinesuccess}');		
	}
}

function inlinesuccess_uninstall()
{
	global $db, $cache, $PL;
	
	if (!file_exists(PLUGINLIBRARY)) {
		flash_message("The selected plugin could not be uninstalled because <a href=\"http://mods.mybb.com/view/pluginlibrary\">PluginLibrary</a> is missing.", "error");
		admin_redirect("index.php?module=config-plugins");
	}
	
	$PL or require_once PLUGINLIBRARY;
	
	$info         = inlinesuccess_info();
	// delete the plugin from cache
	$shadePlugins = $cache->read('shade_plugins');
	unset($shadePlugins[$info['name']]);
	$cache->update('shade_plugins', $shadePlugins);
	
	$PL->templates_delete('inlinesuccess');
	$PL->settings_delete('inlinesuccess');
	
	// remove $inlinesuccess variable	
	find_replace_multitemplatesets('\{\$inlinesuccess\}', '');
}

global $mybb;

if ($mybb->settings['inlinesuccess_enabled']) {
	$plugins->add_hook("redirect", "inlinesuccess_redirect");
	$plugins->add_hook("global_start", "inlinesuccess_global_start");
	$plugins->add_hook("usercp_start", "inlinesuccess_lang_load");
	$plugins->add_hook("private_do_folders_end", "inlinesuccess_lang_load");
	$plugins->add_hook("private_do_empty_end", "inlinesuccess_lang_load");
	$plugins->add_hook("private_do_send_end", "inlinesuccess_lang_load");
	$plugins->add_hook("private_do_tracking_end", "inlinesuccess_lang_load");
}

// Populate the session and redirects the user to the page he came from
function inlinesuccess_redirect(&$args)
{
	global $mybb, $lang;
	
	if($mybb->user['showredirect'] && !$mybb->settings['inlinesuccess_force'] || $mybb->input['ajax']) {
		return;
	}
	
	if (!session_id()) {
		session_start();
	}
	
	if (!$args['message']) {
		$args['message'] = $lang->redirect;
	}
	
	$time    = TIME_NOW;
	$timenow = my_date($mybb->settings['dateformat'], $time) . " " . my_date($mybb->settings['timeformat'], $time);
	
	if (!$args['title']) {
		$args['title'] = $mybb->settings['bbname'];
	}
	
	$url = htmlspecialchars_decode($args['url']);
	$url = str_replace(array(
		"\n",
		"\r",
		";"
	), "", $url);
	
	// After running any shutdown functions...
	run_shutdown();
	
	// ... append the message to the _SESSION and let another function do the rest
	$_SESSION['inlinesuccess'] = array(
		'message' => $args['message']
	);
	
	if (my_substr($url, 0, 7) !== 'http://' && my_substr($url, 0, 8) !== 'https://' && my_substr($url, 0, 1) !== '/') {
		header("Location: {$mybb->settings['bburl']}/{$url}");
	} else {
		header("Location: {$url}");
	}
	
	exit;
}

// Populates the $success variable
function inlinesuccess_global_start()
{
	global $mybb;
	
	if($mybb->user['showredirect'] && !$mybb->settings['inlinesuccess_force'] || $mybb->input['ajax']) {
		return;
	}
	
	global $inlinesuccess, $templates;
	
	if (!session_id()) {
		session_start();
	}
	
	// Hell yeah, we've got a message to show!
	if ($_SESSION['inlinesuccess']) {
	
		$messagelist = $_SESSION['inlinesuccess']['message'];
		eval("\$inlinesuccess = \"" . $templates->get("inlinesuccess_success") . "\";");
		
		// Aaaand we're done here
		unset($_SESSION['inlinesuccess']);
		
	}
}

// Loads our lang variables into usercp, replacing the core ones
function inlinesuccess_lang_load()
{
	global $mybb;
	
	if($mybb->user['showredirect'] && !$mybb->settings['inlinesuccess_force'] || $mybb->input['ajax']) {
		return;
	}
	
	global $lang;
	
	$lang->load("inlinesuccess");
}

// Advanced find and replace function for multiple templates at once using regular expressions (POSIX in MySQL(i), PCRE in PHP, automatically handled)
function find_replace_multitemplatesets($find, $replace)
{
	global $db, $mybb;
	
	$return = false;
	
	// Select all templates
	$query = $db->simple_select("templates", "tid, sid, template, title", "template REGEXP '" . preg_quote($find) . "'");
	while ($template = $db->fetch_array($query)) {
	
		// Replace the content
		$new_template = preg_replace("#" . $find . "#i", $replace, $template['template']);
		if ($new_template == $template['template']) {
			continue;
		}
		
		// The template is a custom template. Replace as normal.
		$updated_template = array(
			"template" => $db->escape_string($new_template)
		);
		
		$db->update_query("templates", $updated_template, "tid='{$template['tid']}'");
		
		$return = true;
	}
	
	return $return;
}