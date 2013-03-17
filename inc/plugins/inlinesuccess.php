<?php
/**
 * Inline Success Messages
 * 
 * Adds support for inline success messages instead of redirection.
 *
 * @package Inline Success Messages
 * @author  Shade <legend_k@live.it>
 * @license http://www.gnu.org/licenses/ GNU/GPL license
 * @version beta 2
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
		'description' => 'Adds support for inline success messages in UCP instead of an (un)friendly redirection page.',
		'website' => 'http://www.idevicelab.net/forum',
		'author' => 'Shade',
		'authorsite' => 'http://www.idevicelab.net/forum',
		'version' => 'beta 2',
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
	global $db, $mybb, $cache, $PL;
	
	if (!file_exists(PLUGINLIBRARY)) {
		flash_message("The selected plugin could not be installed because <a href=\"http://mods.mybb.com/view/pluginlibrary\">PluginLibrary</a> is missing.", "error");
		admin_redirect("index.php?module=config-plugins");
	}
	
	$info = inlinesuccess_info();
	$shadePlugins = $cache->read('shade_plugins');
	$shadePlugins[$info['name']] = array(
		'title' => $info['name'],
		'version' => $info['version']
	);
	$cache->update('shade_plugins', $shadePlugins);
	
	$PL or require_once PLUGINLIBRARY;
	
	// begin our huge core replacements!
	$PL->edit_core('inlinesuccess', 'usercp.php', array(
		// avatar
		array(
			'search' => 'redirect("usercp.php", $lang->redirect_avatarupdated);',
			'replace' => '$inlinesuccess = inline_success($lang->redirect_avatarupdated);
$mybb->input[\'action\'] = "avatar";
unset($avatar_error);'
		),
		// avatar - show new content directly
		array(
			'search' => 'eval("\$currentavatar = \"".$templates->get("usercp_avatar_current")."\";");',
			'before' => 'if($updated_avatar) {
	$urltoavatar = htmlspecialchars_uni($updated_avatar[\'avatar\']);
}'
		),
		// profile
		array(
			'search' => 'redirect("usercp.php", $lang->redirect_profileupdated);',
			'replace' => '$inlinesuccess = inline_success($lang->redirect_profileupdated);
$mybb->input[\'action\'] = "profile";
unset($errors);'
		),
		// profile - show new content directly
		array(
			'search' => 'if($errors)
	{
		$user = $mybb->input;
		$bday = array();
		$bday[0] = $mybb->input[\'bday1\'];
		$bday[1] = $mybb->input[\'bday2\'];
		$bday[2] = intval($mybb->input[\'bday3\']);
	}
	else
	{
		$user = $mybb->user;
		$bday = explode("-", $user[\'birthday\']);
	}',
			'replace' => 'if($errors || $inlinesuccess)
	{
		$user = $mybb->input;
		$bday = array();
		$bday[0] = $mybb->input[\'bday1\'];
		$bday[1] = $mybb->input[\'bday2\'];
		$bday[2] = intval($mybb->input[\'bday3\']);
	}
	else
	{
		$user = $mybb->user;
		$bday = explode("-", $user[\'birthday\']);
	}'
		),
		// options
		array(
			'search' => 'redirect("usercp.php", $lang->redirect_optionsupdated);',
			'replace' => '$inlinesuccess = inline_success($lang->redirect_optionsupdated);
$mybb->input[\'action\'] = "options";
unset($errors);'
		),
		// options - show new content directly
		array(
			'search' => 'if($errors != \'\')
	{
		$user = $mybb->input;
	}
	else
	{
		$user = $mybb->user;
	}',
			'replace' => 'if($errors || $inlinesuccess)
	{
		$user = $mybb->input;
	}
	else
	{
		$user = $mybb->user;
	}'
		),
		// email
		array(
			'search' => 'redirect("usercp.php", $lang->redirect_emailupdated);',
			'replace' => '$inlinesuccess = inline_success($lang->redirect_emailupdated);
$mybb->input[\'action\'] = "email";
unset($errors);'
		),
		// password
		array(
			'search' => 'redirect("usercp.php", $lang->redirect_passwordupdated);',
			'replace' => '$inlinesuccess = inline_success($lang->redirect_passwordupdated);
$mybb->input[\'action\'] = "password";
unset($errors);'
		),
		// username
		array(
			'search' => 'redirect("usercp.php", $lang->redirect_namechanged);',
			'replace' => '$inlinesuccess = inline_success($lang->redirect_namechanged);
$mybb->input[\'action\'] = "changename";
unset($errors);'
		),
		// signature
		array(
			'search' => 'redirect("usercp.php?action=editsig", $lang->redirect_sigupdated);',
			'replace' => '$inlinesuccess = inline_success($lang->redirect_sigupdated);
$mybb->input[\'action\'] = "editsig";
unset($error);'
		),
		// notepad
		array(
			'search' => 'redirect("usercp.php", $lang->redirect_notepadupdated);',
			'replace' => '$inlinesuccess = inline_success($lang->redirect_notepadupdated);
$mybb->input[\'action\'] = \'\';'
		)
	), true);
	
	// ProfilePic plugin support - yay!
	if (file_exists('inc/plugins/profilepic.php')) {
		$PL->edit_core('inlinesuccess', 'inc/plugins/profilepic.php', array(
			// profilepic plugin
			array(
				'search' => 'redirect("usercp.php", $lang->redirect_profilepicupdated);',
				'replace' => '$inlinesuccess = inline_success($lang->redirect_profilepicupdated);
$mybb->input[\'action\'] = "profilepic";
unset($profilepic_error);'
			)
		), true);
	}
	
	// Thought about XThreads (profile fields) too, although most of the people probably doesn't have it installed
	if (file_exists('inc/plugins/xt_proffields.php')) {
		
		$PL->edit_core('inlinesuccess', 'inc/plugins/xt_proffields.php', array(
			array(
				'search' => 'global $templates,$mybb,$customfields,$lang,$theme,$user,$errors,$xtpf_inp,$xtpf_data;',
				'replace' => 'global $templates,$mybb,$customfields,$lang,$theme,$user,$errors,$xtpf_inp,$xtpf_data,$inlinesuccess;'
			),
			array(
				'search' => '$code = xt_proffields_inp($profilefield,$user,$errors,$vars);',
				'replace' => '$code = xt_proffields_inp($profilefield,$user,$errors,$vars,$inlinesuccess);'
			),
			array(
				'search' => 'function xt_proffields_inp(&$pa,&$user,&$errors,&$vars=array()){',
				'replace' => 'function xt_proffields_inp(&$pa,&$user,&$errors,&$vars=array(),&$inlinesuccess){'
			),
			array(
				'search' => 'if($errors){',
				'replace' => 'if($errors || $inlinesuccess){'
			)
		), true);
		
	}
	
	// Euantor's templating system	   
	$dir = new DirectoryIterator(dirname(__FILE__) . '/InlineSuccess/templates');
	$templates = array();
	foreach ($dir as $file) {
		if (!$file->isDot() AND !$file->isDir() AND pathinfo($file->getFilename(), PATHINFO_EXTENSION) == 'html') {
			$templates[$file->getBasename('.html')] = file_get_contents($file->getPathName());
		}
	}
	
	$PL->templates('inlinesuccess', 'Inline Success Messages', $templates);
	
	find_replace_multitemplatesets('{$errors}', '{$errors}{$inlinesuccess}');
	find_replace_multitemplatesets('{$avatar_error}', '{$avatar_error}{$inlinesuccess}');
	find_replace_multitemplatesets('{$profilepic_error}', '{$profilepic_error}{$inlinesuccess}');
	
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
	
	// oh dear, so many core edits passing away!
	$PL->edit_core('inlinesuccess', 'usercp.php', array(), true);
	
	if (file_exists('inc/plugins/profilepic.php')) {
		$PL->edit_core('inlinesuccess', 'inc/plugins/profilepic.php', array(), true);
	}
	
	if (file_exists('inc/plugins/xt_proffields.php')) {
		$PL->edit_core('inlinesuccess', 'inc/plugins/xt_proffields.php', array(), true);
	}
	
	$PL->templates_delete('inlinesuccess');
	
	find_replace_multitemplatesets('{$inlinesuccess}', '');
}

/**
 * Throws a success message instead of a "un"friendly redirect
 *
 * @param mixed An array or string containing the messages to push to the DOM
 * @return string The generated code
 */

function inline_success($messages)
{
	global $theme, $mybb, $db, $lang, $templates;
	
	if (!is_array($messages)) {
		$messages = array(
			$messages
		);
	}
	
	foreach ($messages as $message) {
		$messagelist .= $message . "\n";
	}
	
	eval("\$inlinesuccess = \"" . $templates->get("inlinesuccess_success_inline") . "\";");
	
	return $inlinesuccess;
}

/**
 * Simple find and replace function for multiple templates at once.
 *
 * @param string The pattern to find in templates, thus will be replaced by the replacement.
 * @param string The string that will replace the pattern.
 * @return boolean True if successful, false if unsuccessful
 */

function find_replace_multitemplatesets($find, $replace)
{
	global $db, $mybb;
	
	$return = false;
	
	// Select all other modified templates with that title
	$query = $db->simple_select("templates", "tid, sid, template, title", "template LIKE '%" . $db->escape_string($find) . "%'");
	while ($template = $db->fetch_array($query)) {
		// replace the content
		$new_template = str_replace($find, $replace, $template['template']);
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